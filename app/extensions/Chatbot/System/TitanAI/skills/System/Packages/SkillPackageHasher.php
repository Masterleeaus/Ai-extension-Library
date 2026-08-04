<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

use InvalidArgumentException;
use JsonException;

final class SkillPackageHasher
{
    /**
     * @param array<string, mixed> $manifest
     * @param list<SkillResourceFingerprint> $resources
     */
    public function fingerprint(array $manifest, string $instructions, array $resources = []): SkillPackageFingerprint
    {
        $canonicalManifest = $this->canonicalise($manifest);
        $normalisedInstructions = $this->normaliseText($instructions);
        $resourceRecords = [];

        foreach ($resources as $resource) {
            if (! $resource instanceof SkillResourceFingerprint) {
                throw new InvalidArgumentException('Package resources must be SkillResourceFingerprint values.');
            }
            if (isset($resourceRecords[$resource->path])) {
                throw new InvalidArgumentException("Duplicate package resource path [{$resource->path}].");
            }
            $resourceRecords[$resource->path] = [
                'sha256' => $resource->sha256,
                'size' => $resource->size,
            ];
        }
        ksort($resourceRecords, SORT_STRING);

        $manifestJson = $this->encode($canonicalManifest);
        $instructionsHash = hash('sha256', $normalisedInstructions);
        $manifestHash = hash('sha256', $manifestJson);

        $packagePayload = [
            'contract' => 'system-ai-chat-skill-package/1',
            'manifest_hash' => $manifestHash,
            'instructions_hash' => $instructionsHash,
            'resources' => $resourceRecords,
        ];

        return new SkillPackageFingerprint(
            packageHash: hash('sha256', $this->encode($packagePayload)),
            manifestHash: $manifestHash,
            instructionsHash: $instructionsHash,
            resourceHashes: array_map(static fn (array $record): string => $record['sha256'], $resourceRecords),
        );
    }

    public function normaliseText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return rtrim($value) . "\n";
    }

    public function canonicalJson(mixed $value): string
    {
        return $this->encode($this->canonicalise($value));
    }

    private function canonicalise(mixed $value): mixed
    {
        if (! is_array($value)) {
            if (is_float($value) && ! is_finite($value)) {
                throw new InvalidArgumentException('Non-finite numbers cannot be canonicalised.');
            }

            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalise($item), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalise($item);
        }

        return $value;
    }

    private function encode(mixed $value): string
    {
        try {
            return (string) json_encode(
                $value,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Skill package data cannot be canonicalised: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
