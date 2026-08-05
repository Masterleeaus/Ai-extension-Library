<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical;

use InvalidArgumentException;
use JsonException;
use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;
use TitanZero\Interaction\Vertical\DTO\VerticalContextSnapshot;

final class VerticalContextComposer
{
    public function __construct(
        private readonly ?GenericVerticalBaseProvider $genericBase = null,
    ) {}

    /**
     * @param list<VerticalContextLayer|array> $layers
     */
    public function compose(array $layers, array $identity = []): VerticalContextSnapshot
    {
        $normalized = [];
        foreach ($layers as $index => $layer) {
            $layer = is_array($layer) ? VerticalContextLayer::fromArray($layer) : $layer;
            if (!$layer instanceof VerticalContextLayer) {
                throw new InvalidArgumentException("Context layer at index {$index} is invalid.");
            }
            $normalized[] = ['index' => $index + 1, 'layer' => $layer];
        }

        if (!$this->hasPlatformBase($normalized)) {
            array_unshift($normalized, [
                'index' => 0,
                'layer' => ($this->genericBase ?? new GenericVerticalBaseProvider())->layer($identity),
            ]);
        }

        $this->assertUniqueIds($normalized);
        usort($normalized, static function (array $left, array $right): int {
            $byPrecedence = $left['layer']->precedence <=> $right['layer']->precedence;
            return $byPrecedence !== 0 ? $byPrecedence : $left['index'] <=> $right['index'];
        });

        $resolved = array_fill_keys(VerticalContextLayer::VALUE_SECTIONS, []);
        $sources = [];
        $constraints = [];
        $orderedLayers = [];

        foreach ($normalized as $entry) {
            /** @var VerticalContextLayer $layer */
            $layer = $entry['layer'];
            $orderedLayers[] = $layer;
            $constraints = $this->merge($constraints, $layer->constraints);
            foreach ($layer->values as $section => $values) {
                $resolved[$section] = $this->mergeWithProvenance(
                    $resolved[$section],
                    $values,
                    '/resolved/' . $this->escapePointer($section),
                    $layer,
                    $sources,
                );
            }
        }

        $layerArrays = array_map(
            static fn (VerticalContextLayer $layer): array => $layer->toArray(),
            $orderedLayers,
        );

        $payload = [
            'schema_version' => 1,
            'company_id' => $identity['company_id'] ?? null,
            'wizard' => (array) ($identity['wizard'] ?? []),
            'actor' => (array) ($identity['actor'] ?? []),
            'locale' => (array) ($identity['locale'] ?? []),
            'layers' => $layerArrays,
            'resolved' => $resolved,
            'sources' => $sources,
            'approvals' => (array) ($identity['approvals'] ?? []),
            'unanswered' => array_values((array) ($identity['unanswered'] ?? [])),
            'constraints' => $constraints,
        ];

        $hashPayload = $payload;
        foreach ($hashPayload['layers'] as &$layerMetadata) {
            unset($layerMetadata['generated_at']);
        }
        unset($layerMetadata);

        $hash = hash('sha256', $this->canonicalJson($hashPayload));
        $data = [
            ...$payload,
            'context_id' => 'ctx_' . substr($hash, 0, 24),
            'context_hash' => $hash,
            'generated_at' => gmdate(DATE_ATOM),
        ];

        return new VerticalContextSnapshot($data, $orderedLayers, $hash);
    }

    private function hasPlatformBase(array $entries): bool
    {
        foreach ($entries as $entry) {
            if ($entry['layer']->kind === 'platform_base') {
                return true;
            }
        }
        return false;
    }

    private function assertUniqueIds(array $entries): void
    {
        $seen = [];
        foreach ($entries as $entry) {
            $id = $entry['layer']->id;
            if (isset($seen[$id])) {
                throw new InvalidArgumentException("Duplicate vertical context layer ID: {$id}.");
            }
            $seen[$id] = true;
        }
    }

    private function merge(array $current, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (is_array($value) && !array_is_list($value)
                && isset($current[$key]) && is_array($current[$key]) && !array_is_list($current[$key])) {
                $current[$key] = $this->merge($current[$key], $value);
            } else {
                $current[$key] = $value;
            }
        }
        return $current;
    }

    private function mergeWithProvenance(
        array $current,
        array $incoming,
        string $pointer,
        VerticalContextLayer $layer,
        array &$sources,
    ): array {
        foreach ($incoming as $key => $value) {
            $leafPointer = $pointer . '/' . $this->escapePointer((string) $key);
            if (is_array($value) && !array_is_list($value)) {
                $existingIsObject = isset($current[$key])
                    && is_array($current[$key])
                    && !array_is_list($current[$key]);
                if (!$existingIsObject) {
                    $this->removeSourceSubtree($sources, $leafPointer);
                } else {
                    unset($sources[$leafPointer]);
                }
                $existing = $existingIsObject ? $current[$key] : [];
                $current[$key] = $this->mergeWithProvenance($existing, $value, $leafPointer, $layer, $sources);
                continue;
            }

            $this->removeSourceSubtree($sources, $leafPointer);
            $current[$key] = $value;
            $sources[$leafPointer] = $layer->provenance->toArray();
        }
        return $current;
    }

    private function removeSourceSubtree(array &$sources, string $pointer): void
    {
        $prefix = $pointer . '/';
        foreach (array_keys($sources) as $sourcePointer) {
            if ($sourcePointer === $pointer || str_starts_with($sourcePointer, $prefix)) {
                unset($sources[$sourcePointer]);
            }
        }
    }

    private function escapePointer(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }

    private function canonicalJson(array $value): string
    {
        try {
            return json_encode(
                $this->canonicalize($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Vertical context cannot be serialized.', previous: $exception);
        }
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }
        return $value;
    }
}
