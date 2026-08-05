<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

use InvalidArgumentException;

final class LayeredWizardQuestionComposer
{
    public const DEFINITION_KEY = 'layered-company-launch';

    /** @param array<string,mixed>|null $catalogue */
    public function __construct(private ?array $catalogue = null)
    {
        $this->catalogue ??= LayeredWizardQuestionCatalogue::defaults();
        $this->validateCatalogue($this->catalogue);
    }

    public function key(): string
    {
        return self::DEFINITION_KEY;
    }

    /** @param array<string,mixed> $request @return array<string,mixed> */
    public function compose(array $request = []): array
    {
        $layers = $this->selectLayers($request);
        $sections = [];
        $protected = $this->protectedFingerprints();

        foreach ($layers as $layer) {
            if (($layer['dynamic'] ?? false) === true) {
                $this->applyDynamicLayer($sections, $layer);
                continue;
            }
            $this->applyCatalogueLayer($sections, $layer);
        }

        $this->validateProtectedQuestions($sections, $protected);
        $this->validateQuestionSet($sections);
        $sectionList = $this->finalizeSections($sections);
        $definition = [
            ...$this->catalogue['definition'],
            'schema_version' => (string) $this->catalogue['schema_version'],
            'sections' => $sectionList,
            'dependency_graph' => $this->dependencyGraph($sectionList),
            'composition' => $this->compositionMetadata($request, $layers),
        ];
        $definition['composition_hash'] = hash(
            'sha256',
            json_encode($this->canonicalize($definition), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        return $definition;
    }

    /** @return list<array<string,mixed>> */
    private function selectLayers(array $request): array
    {
        $selected = [$this->catalogueLayer('generic.company-launch')];

        $country = strtoupper(trim((string) ($request['country'] ?? '')));
        if ($country !== '' && isset($this->catalogue['layers']['country.' . strtolower($country)])) {
            $selected[] = $this->catalogueLayer('country.' . strtolower($country));
        }

        $family = $this->slug((string) ($request['vertical_family'] ?? ''));
        if ($family !== '' && isset($this->catalogue['layers']['vertical.' . $family])) {
            $selected[] = $this->catalogueLayer('vertical.' . $family);
        }

        $subtype = $this->slug((string) ($request['subtype'] ?? ''));
        $subtypeKey = 'subtype.' . $subtype;
        $knownSubtype = $subtype !== ''
            && isset($this->catalogue['layers'][$subtypeKey])
            && ($subtype !== 'cleaning' || $family === 'field-home-services');
        if ($knownSubtype) {
            $selected[] = $this->catalogueLayer($subtypeKey);
        } else {
            $selected[] = $this->catalogueLayer('fallback.generic-service');
        }

        $capabilities = array_values(array_unique(array_filter(array_map(
            fn (mixed $value): string => $this->slug((string) $value),
            (array) ($request['capabilities'] ?? []),
        ), static fn (string $value): bool => $value !== '')));
        sort($capabilities, SORT_STRING);
        foreach ($capabilities as $capability) {
            $key = 'capability.' . $capability;
            if (isset($this->catalogue['layers'][$key])) {
                $selected[] = $this->catalogueLayer($key);
            }
        }

        if (isset($request['tenant_layer'])) {
            $selected[] = $this->dynamicLayer((array) $request['tenant_layer'], 'tenant', 60);
        }
        if (isset($request['ai_layer'])) {
            $selected[] = $this->dynamicLayer((array) $request['ai_layer'], 'ai', 70);
        }

        usort($selected, static function (array $left, array $right): int {
            return [(int) $left['precedence'], (string) $left['id']]
                <=> [(int) $right['precedence'], (string) $right['id']];
        });

        return $selected;
    }

    private function catalogueLayer(string $key): array
    {
        $layer = $this->catalogue['layers'][$key] ?? null;
        if (!is_array($layer)) {
            throw new InvalidArgumentException("Wizard question layer [{$key}] is not registered.");
        }
        $layer['dynamic'] = false;

        return $layer;
    }

    private function dynamicLayer(array $layer, string $kind, int $precedence): array
    {
        $id = trim((string) ($layer['id'] ?? ''));
        $version = trim((string) ($layer['version'] ?? ''));
        $prefix = $kind . '.';
        if ($id === '' || !str_starts_with($id, $prefix) || preg_match('/^[a-z][a-z0-9._-]{2,127}$/', $id) !== 1) {
            throw new InvalidArgumentException("{$kind} question layer has an invalid stable ID.");
        }
        if (preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw new InvalidArgumentException("{$kind} question layer must use a semantic version.");
        }

        return [
            'id' => $id,
            'kind' => $kind,
            'version' => $version,
            'precedence' => $precedence,
            'dynamic' => true,
            'wording' => (array) ($layer['wording'] ?? []),
            'questions' => (array) ($layer['questions'] ?? []),
            'remove_questions' => (array) ($layer['remove_questions'] ?? []),
        ];
    }

    /** @param array<string,array<string,mixed>> $sections */
    private function applyCatalogueLayer(array &$sections, array $layer): void
    {
        foreach ((array) ($layer['sections'] ?? []) as $sectionKey => $section) {
            if (!is_array($section)) {
                throw new InvalidArgumentException("Layer [{$layer['id']}] contains an invalid section.");
            }
            $key = trim((string) ($section['key'] ?? $sectionKey));
            if ($key === '') {
                throw new InvalidArgumentException("Layer [{$layer['id']}] contains a section without a key.");
            }
            $existing = $sections[$key] ?? [
                'key' => $key,
                'title' => $key,
                'order' => 1000,
                'approval_mode' => 'none',
                'questions' => [],
                'source_layers' => [],
            ];
            foreach (['title', 'order', 'approval_mode'] as $attribute) {
                if (array_key_exists($attribute, $section)) {
                    $existing[$attribute] = $section[$attribute];
                }
            }
            $existing['source_layers'][] = (string) $layer['id'];
            $existing['source_layers'] = array_values(array_unique($existing['source_layers']));

            foreach ((array) ($section['questions'] ?? []) as $questionKey => $question) {
                if (!is_array($question)) {
                    throw new InvalidArgumentException("Layer [{$layer['id']}] contains an invalid question.");
                }
                $question['key'] = (string) ($question['key'] ?? $questionKey);
                $current = $existing['questions'][$question['key']] ?? null;
                $history = is_array($current) ? (array) ($current['source_history'] ?? []) : [];
                $history[] = (string) $layer['id'];
                $normal = [
                    ...(is_array($current) ? $current : []),
                    ...$question,
                    'source_layer' => (string) $layer['id'],
                    'source_layer_kind' => (string) $layer['kind'],
                    'source_history' => array_values(array_unique($history)),
                ];
                $existing['questions'][$normal['key']] = $normal;
            }

            $sections[$key] = $existing;
        }
    }

    /** @param array<string,array<string,mixed>> $sections */
    private function applyDynamicLayer(array &$sections, array $layer): void
    {
        $kind = (string) $layer['kind'];
        $removals = array_values(array_unique(array_map('strval', (array) $layer['remove_questions'])));
        if ($kind === 'ai' && $removals !== []) {
            throw new InvalidArgumentException('AI question layers cannot remove existing questions.');
        }
        foreach ($removals as $questionKey) {
            $location = $this->locateQuestion($sections, $questionKey);
            if ($location === null) {
                continue;
            }
            [$sectionKey, $question] = $location;
            if (($question['protected'] ?? false) === true) {
                throw new InvalidArgumentException("Protected question [{$questionKey}] cannot be removed.");
            }
            unset($sections[$sectionKey]['questions'][$questionKey]);
        }

        $wording = (array) $layer['wording'];
        ksort($wording, SORT_STRING);
        foreach ($wording as $questionKey => $changes) {
            if (!is_array($changes)) {
                throw new InvalidArgumentException("Wording change [{$questionKey}] must be an object.");
            }
            $unsupported = array_diff(array_keys($changes), ['question', 'help_text']);
            if ($unsupported !== []) {
                throw new InvalidArgumentException('AI and tenant wording layers may only change question and help_text.');
            }
            $location = $this->locateQuestion($sections, (string) $questionKey);
            if ($location === null) {
                throw new InvalidArgumentException("Wording target [{$questionKey}] does not exist.");
            }
            [$sectionKey] = $location;
            foreach ($changes as $attribute => $value) {
                if (!is_string($value) || trim($value) === '' || mb_strlen($value) > 1000) {
                    throw new InvalidArgumentException("Wording value [{$questionKey}.{$attribute}] is invalid.");
                }
                $sections[$sectionKey]['questions'][$questionKey][$attribute] = trim($value);
            }
            $sections[$sectionKey]['questions'][$questionKey]['wording_source_layer'] = (string) $layer['id'];
        }

        $questions = array_values((array) $layer['questions']);
        usort($questions, static fn (array $left, array $right): int => (string) ($left['key'] ?? '') <=> (string) ($right['key'] ?? ''));
        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                throw new InvalidArgumentException("Layer [{$layer['id']}] contains an invalid extension question.");
            }
            $slotKey = trim((string) ($question['slot'] ?? ''));
            $slot = $this->catalogue['extension_slots'][$slotKey] ?? null;
            if (!is_array($slot) || !in_array($kind, (array) ($slot['sources'] ?? []), true)) {
                throw new InvalidArgumentException("Layer [{$layer['id']}] cannot use extension slot [{$slotKey}].");
            }
            $key = trim((string) ($question['key'] ?? ''));
            if (!str_starts_with($key, $kind . '.') || preg_match('/^[a-z][a-z0-9._-]{2,127}$/', $key) !== 1) {
                throw new InvalidArgumentException("Layer [{$layer['id']}] contains an invalid semantic question key.");
            }
            if ($this->locateQuestion($sections, $key) !== null) {
                throw new InvalidArgumentException("Question key [{$key}] is already defined.");
            }
            $required = (bool) ($question['required'] ?? false);
            $risk = (string) ($question['risk'] ?? 'low');
            if ($kind === 'ai' && ($required || $risk !== 'low')) {
                throw new InvalidArgumentException('AI extension questions must be optional and low risk.');
            }
            if ($kind === 'tenant' && $risk === 'critical') {
                throw new InvalidArgumentException('Tenant extension questions cannot be critical-risk authority controls.');
            }

            $sectionKey = (string) $slot['section'];
            if (!isset($sections[$sectionKey])) {
                throw new InvalidArgumentException("Extension slot [{$slotKey}] targets a missing section.");
            }
            unset($question['slot']);
            $sections[$sectionKey]['questions'][$key] = [
                ...$question,
                'key' => $key,
                'required' => $required,
                'risk' => $risk,
                'order' => (int) $slot['order'] + $index,
                'extension_slot' => $slotKey,
                'source_layer' => (string) $layer['id'],
                'source_layer_kind' => $kind,
                'source_history' => [(string) $layer['id']],
            ];
            $sections[$sectionKey]['source_layers'][] = (string) $layer['id'];
            $sections[$sectionKey]['source_layers'] = array_values(array_unique($sections[$sectionKey]['source_layers']));
        }
    }

    /** @return array{0:string,1:array<string,mixed>}|null */
    private function locateQuestion(array $sections, string $questionKey): ?array
    {
        foreach ($sections as $sectionKey => $section) {
            if (isset($section['questions'][$questionKey])) {
                return [(string) $sectionKey, (array) $section['questions'][$questionKey]];
            }
        }

        return null;
    }

    /** @return array<string,array<string,mixed>> */
    private function protectedFingerprints(): array
    {
        $layer = $this->catalogueLayer('generic.company-launch');
        $protected = [];
        foreach ((array) $layer['sections'] as $section) {
            foreach ((array) ($section['questions'] ?? []) as $question) {
                if (($question['protected'] ?? false) !== true) {
                    continue;
                }
                $key = (string) $question['key'];
                $protected[$key] = $this->questionFingerprint($question);
            }
        }

        return $protected;
    }

    private function validateProtectedQuestions(array $sections, array $protected): void
    {
        $domains = [];
        foreach ($protected as $key => $fingerprint) {
            $location = $this->locateQuestion($sections, $key);
            if ($location === null) {
                throw new InvalidArgumentException("Mandatory question [{$key}] was removed.");
            }
            [, $question] = $location;
            if ($this->questionFingerprint($question) !== $fingerprint) {
                throw new InvalidArgumentException("Mandatory question [{$key}] changed its protected schema.");
            }
            $domain = (string) ($question['mandatory_domain'] ?? '');
            if ($domain === '' || isset($domains[$domain])) {
                throw new InvalidArgumentException('Mandatory question domains must be complete and unique.');
            }
            $domains[$domain] = true;
        }
        $expected = ['activation', 'compliance', 'evidence', 'identity', 'payment', 'permission', 'privacy'];
        $actual = array_keys($domains);
        sort($actual, SORT_STRING);
        if ($actual !== $expected) {
            throw new InvalidArgumentException('Mandatory question domains are incomplete.');
        }
    }

    private function questionFingerprint(array $question): array
    {
        return [
            'key' => (string) ($question['key'] ?? ''),
            'response_type' => (string) ($question['response_type'] ?? ''),
            'required' => (bool) ($question['required'] ?? false),
            'risk' => (string) ($question['risk'] ?? ''),
            'protected' => (bool) ($question['protected'] ?? false),
            'mandatory_domain' => (string) ($question['mandatory_domain'] ?? ''),
        ];
    }

    private function validateQuestionSet(array $sections): void
    {
        $seen = [];
        foreach ($sections as $section) {
            foreach ((array) ($section['questions'] ?? []) as $question) {
                $key = (string) ($question['key'] ?? '');
                if (preg_match('/^[a-z][a-z0-9._-]{2,127}$/', $key) !== 1 || isset($seen[$key])) {
                    throw new InvalidArgumentException("Question key [{$key}] is invalid or duplicated.");
                }
                $seen[$key] = true;
                if (!is_string($question['question'] ?? null) || trim((string) $question['question']) === '') {
                    throw new InvalidArgumentException("Question [{$key}] has no wording.");
                }
                if (!is_string($question['response_type'] ?? null) || trim((string) $question['response_type']) === '') {
                    throw new InvalidArgumentException("Question [{$key}] has no response type.");
                }
                if (!is_bool($question['required'] ?? null)) {
                    throw new InvalidArgumentException("Question [{$key}] required must be boolean.");
                }
                if (!in_array((string) ($question['risk'] ?? ''), ['low', 'medium', 'high', 'critical'], true)) {
                    throw new InvalidArgumentException("Question [{$key}] has an invalid risk.");
                }
                if (!is_int($question['order'] ?? null)) {
                    throw new InvalidArgumentException("Question [{$key}] requires an integer order.");
                }
            }
        }
    }

    /** @return list<array<string,mixed>> */
    private function finalizeSections(array $sections): array
    {
        foreach ($sections as &$section) {
            $questions = array_values((array) $section['questions']);
            usort($questions, static fn (array $left, array $right): int => [(int) $left['order'], (string) $left['key']] <=> [(int) $right['order'], (string) $right['key']]);
            $section['questions'] = $questions;
            $section['source_layers'] = array_values(array_unique((array) $section['source_layers']));
        }
        unset($section);
        $sections = array_values($sections);
        usort($sections, static fn (array $left, array $right): int => [(int) $left['order'], (string) $left['key']] <=> [(int) $right['order'], (string) $right['key']]);

        return $sections;
    }

    private function dependencyGraph(array $sections): array
    {
        $graph = [];
        foreach ($sections as $section) {
            foreach ((array) $section['questions'] as $question) {
                $dependsOn = array_values(array_unique(array_filter(array_merge(
                    array_map('strval', (array) ($question['depends_on'] ?? [])),
                    $this->conditionFields((array) ($question['when'] ?? [])),
                    $this->conditionFields((array) (($question['priority_when']['condition'] ?? []))),
                ))));
                sort($dependsOn, SORT_STRING);
                $graph[(string) $question['key']] = [
                    'depends_on' => $dependsOn,
                    'when' => (array) ($question['when'] ?? []),
                    'priority_when' => (array) ($question['priority_when'] ?? []),
                    'source_layer' => (string) $question['source_layer'],
                ];
            }
        }
        ksort($graph, SORT_STRING);

        return $graph;
    }

    /** @return list<string> */
    private function conditionFields(array $condition): array
    {
        if ($condition === []) {
            return [];
        }
        $fields = [];
        if (isset($condition['field']) && trim((string) $condition['field']) !== '') {
            $fields[] = trim((string) $condition['field']);
        }
        foreach (['all', 'any'] as $group) {
            foreach ((array) ($condition[$group] ?? []) as $child) {
                $fields = [...$fields, ...$this->conditionFields((array) $child)];
            }
        }
        if (isset($condition['not'])) {
            $fields = [...$fields, ...$this->conditionFields((array) $condition['not'])];
        }

        return array_values(array_unique($fields));
    }

    private function compositionMetadata(array $request, array $layers): array
    {
        $requestedCapabilities = array_values(array_unique(array_filter(array_map(
            fn (mixed $value): string => $this->slug((string) $value),
            (array) ($request['capabilities'] ?? []),
        ))));
        sort($requestedCapabilities, SORT_STRING);
        $knownCapabilities = array_values(array_filter($requestedCapabilities, fn (string $capability): bool => isset($this->catalogue['layers']['capability.' . $capability])));
        $unsupportedCapabilities = array_values(array_diff($requestedCapabilities, $knownCapabilities));
        $layerIds = array_map(static fn (array $layer): string => (string) $layer['id'], $layers);

        return [
            'country' => strtoupper(trim((string) ($request['country'] ?? ''))),
            'vertical_family' => $this->slug((string) ($request['vertical_family'] ?? '')),
            'subtype' => $this->slug((string) ($request['subtype'] ?? '')),
            'capabilities' => $knownCapabilities,
            'unsupported_capabilities' => $unsupportedCapabilities,
            'layers' => $layerIds,
            'layer_versions' => array_combine(
                $layerIds,
                array_map(static fn (array $layer): string => (string) $layer['version'], $layers),
            ),
            'fallback_used' => in_array('fallback.generic-service', $layerIds, true),
        ];
    }

    private function validateCatalogue(array $catalogue): void
    {
        if (($catalogue['schema_version'] ?? null) !== '1.0.0') {
            throw new InvalidArgumentException('Layered wizard catalogue schema version is unsupported.');
        }
        if (($catalogue['definition']['key'] ?? null) !== self::DEFINITION_KEY) {
            throw new InvalidArgumentException('Layered wizard catalogue definition key is invalid.');
        }
        foreach (['generic.company-launch', 'fallback.generic-service'] as $required) {
            if (!isset($catalogue['layers'][$required])) {
                throw new InvalidArgumentException("Layered wizard catalogue is missing [{$required}].");
            }
        }
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map($this->canonicalize(...), $value);
        }
        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
