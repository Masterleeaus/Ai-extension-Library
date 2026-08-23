<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\AbstractSourceConnector;
use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use App\Extensions\Migration\System\Security\ArchiveSafetyGuard;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final class ZipBundleConnector extends AbstractSourceConnector
{
    private ?ArchiveSafetyGuard $archiveGuard = null;
    private ?FileConnectorFactory $factory = null;

    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'zip',
            name: 'ZIP Export Bundle',
            connectorClass: self::class,
            category: 'file',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'bundle_discovery', 'safe_entry_stream', 'attachment_fetch', 'archive_safety'],
            supportsDiscovery: true,
            supportsAttachments: true,
            readOnly: true,
            streamingMode: 'bundle',
        );
    }

    public function testConnection(array $configuration): array
    {
        try {
            $zip = $this->openValidated($this->path($configuration));
            $entries = $zip->numFiles;
            $zip->close();

            return [
                'successful' => true,
                'connector' => 'zip',
                'entries' => $entries,
                'configuration' => $this->redactConfiguration($configuration),
            ];
        } catch (\Throwable $exception) {
            return [
                'successful' => false,
                'connector' => 'zip',
                'message' => $exception->getMessage(),
                'configuration' => $this->redactConfiguration($configuration),
            ];
        }
    }

    public function discover(array $configuration): array
    {
        $path = $this->path($configuration);
        $zip = $this->openValidated($path);
        $entities = [];
        $supportedEntries = [];
        $attachments = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat) || ! isset($stat['name']) || str_ends_with((string) $stat['name'], '/')) {
                    continue;
                }

                $entry = (string) $stat['name'];
                $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                if (! $this->factory()->supportsExtension($extension)) {
                    $attachments[] = [
                        'entry' => $entry,
                        'size' => (int) ($stat['size'] ?? 0),
                    ];
                    continue;
                }

                $supportedEntries[] = $entry;
                $temp = $this->copyEntryToTemp($zip, $entry);
                try {
                    $connector = $this->factory()->forExtension($extension);
                    $inner = $connector->discover([
                        'path' => $temp,
                        'sample_limit' => (int) ($configuration['sample_limit'] ?? 3),
                    ]);
                    foreach (($inner['entities'] ?? []) as $entity) {
                        if (! is_array($entity)) {
                            continue;
                        }
                        $entity['bundle_entry'] = $entry;
                        $entity['name'] = pathinfo($entry, PATHINFO_FILENAME) . ':' . ($entity['name'] ?? 'records');
                        $entities[] = $entity;
                    }
                } finally {
                    @unlink($temp);
                }
            }
        } finally {
            $zip->close();
        }

        return $this->discoveryResult($entities, [
            'source' => ['path' => basename($path), 'format' => 'zip'],
            'supported_entries' => $supportedEntries,
            'attachment_references' => $attachments,
        ]);
    }

    public function stream(array $entityPlan, ?array $checkpoint = null): iterable
    {
        $configuration = $this->configurationFromPlan($entityPlan);
        $path = $this->path($configuration);
        $entry = $configuration['entry'] ?? ($entityPlan['source']['entry'] ?? null);
        if (! is_string($entry) || $entry === '') {
            throw new InvalidArgumentException('ZIP extraction requires a selected entry.');
        }

        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (! $this->factory()->supportsExtension($extension)) {
            throw new InvalidArgumentException('Selected ZIP entry is not a supported structured migration source.');
        }

        $zip = $this->openValidated($path);
        $temp = null;
        try {
            if ($zip->locateName($entry) === false) {
                throw new InvalidArgumentException('Selected ZIP entry does not exist.');
            }
            $temp = $this->copyEntryToTemp($zip, $entry);
            $connector = $this->factory()->forExtension($extension);
            foreach ($connector->stream([
                'configuration' => array_merge($configuration, ['path' => $temp]),
                'source' => $entityPlan['source'] ?? [],
            ], $checkpoint) as $row) {
                yield $row;
            }
        } finally {
            $zip->close();
            if ($temp !== null) {
                @unlink($temp);
            }
        }
    }

    public function fetchAttachment(array $attachment): mixed
    {
        $path = $this->path($attachment);
        $entry = $attachment['entry'] ?? null;
        if (! is_string($entry) || $entry === '') {
            throw new InvalidArgumentException('ZIP attachment fetch requires an entry name.');
        }

        $zip = $this->openValidated($path);
        try {
            $stream = $zip->getStream($entry);
            if (! is_resource($stream)) {
                throw new InvalidArgumentException('ZIP attachment entry does not exist.');
            }
            $target = fopen('php://temp/maxmemory:8388608', 'w+b');
            if ($target === false) {
                fclose($stream);
                throw new RuntimeException('Unable to allocate temporary attachment stream.');
            }
            stream_copy_to_stream($stream, $target);
            fclose($stream);
            rewind($target);

            return $target;
        } finally {
            $zip->close();
        }
    }

    private function openValidated(string $path): ZipArchive
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required for ZIP migration bundles.');
        }

        $zip = new ZipArchive();
        $result = $zip->open($path, ZipArchive::RDONLY);
        if ($result !== true) {
            throw new RuntimeException('Unable to open ZIP migration bundle.');
        }

        $this->guard()->reset();
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat)) {
                    throw new RuntimeException('Unable to read ZIP entry metadata.');
                }
                $this->guard()->assertEntrySafe(
                    (string) ($stat['name'] ?? ''),
                    (int) ($stat['size'] ?? 0),
                    (int) ($stat['comp_size'] ?? 0),
                );
            }
        } catch (\Throwable $exception) {
            $zip->close();
            throw $exception;
        }

        return $zip;
    }

    private function copyEntryToTemp(ZipArchive $zip, string $entry): string
    {
        $source = $zip->getStream($entry);
        if (! is_resource($source)) {
            throw new InvalidArgumentException("Unable to read ZIP entry {$entry}.");
        }
        $temp = tempnam(sys_get_temp_dir(), 'titan-migration-zip-');
        if ($temp === false) {
            fclose($source);
            throw new RuntimeException('Unable to allocate ZIP extraction file.');
        }
        $target = fopen($temp, 'w+b');
        if ($target === false) {
            fclose($source);
            @unlink($temp);
            throw new RuntimeException('Unable to open ZIP extraction file.');
        }

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }

        return $temp;
    }

    private function path(array $configuration): string
    {
        $path = $configuration['path'] ?? null;
        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('ZIP source must exist and be readable.');
        }

        return $path;
    }

    private function guard(): ArchiveSafetyGuard
    {
        return $this->archiveGuard ??= new ArchiveSafetyGuard();
    }

    private function factory(): FileConnectorFactory
    {
        return $this->factory ??= new FileConnectorFactory();
    }
}
