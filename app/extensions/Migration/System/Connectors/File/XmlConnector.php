<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\File;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;
use RuntimeException;
use XMLReader;

final class XmlConnector extends AbstractFileConnector
{
    public function definition(): ConnectorDefinition
    {
        return new ConnectorDefinition(
            key: 'xml',
            name: 'XML File',
            connectorClass: self::class,
            category: 'file',
            authenticationTypes: ['local_file'],
            capabilities: ['connection_test', 'schema_discovery', 'field_profiling', 'xmlreader_stream', 'checkpoint_offset'],
            supportsDiscovery: true,
            readOnly: true,
            streamingMode: 'generator',
        );
    }

    protected function rows(array $configuration): iterable
    {
        if (! class_exists(XMLReader::class)) {
            throw new RuntimeException('XMLReader extension is required for XML migration sources.');
        }

        $reader = new XMLReader();
        if (! $reader->open($this->path($configuration), null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOCDATA)) {
            throw new RuntimeException('Unable to open XML source.');
        }

        $recordElement = isset($configuration['record_element']) ? trim((string) $configuration['record_element']) : '';
        $rootDepth = null;

        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::DOC_TYPE) {
                    throw new RuntimeException('XML DTD declarations are not allowed in migration sources.');
                }

                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    continue;
                }

                if ($rootDepth === null) {
                    $rootDepth = $reader->depth;
                    continue;
                }

                if ($recordElement === '' && $reader->depth === $rootDepth + 1) {
                    $recordElement = $reader->localName;
                }

                if ($recordElement === '' || $reader->localName !== $recordElement) {
                    continue;
                }

                $outer = $reader->readOuterXml();
                if ($outer === '') {
                    continue;
                }

                $xml = simplexml_load_string($outer, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
                if ($xml === false) {
                    throw new RuntimeException("Unable to decode XML record element {$recordElement}.");
                }

                $decoded = json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
                yield is_array($decoded) && ! array_is_list($decoded) ? $decoded : ['value' => $decoded];
            }
        } finally {
            $reader->close();
        }
    }
}
