<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\AI;

use InvalidArgumentException;
use JsonException;
use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;

final class VerticalAIProposalValidator
{
    private const TOP_LEVEL_KEYS = ['updates', 'confidence', 'rationale', 'provenance'];
    private const UPDATE_KEYS = ['terminology', 'context', 'questions'];
    private const QUESTION_TYPES = [
        'text',
        'textarea',
        'number',
        'boolean',
        'single_select',
        'multi_select',
        'date',
        'time',
        'datetime',
    ];

    public function decodeAndValidateRaw(string $json): array
    {
        if ($json === '' || strlen($json) > 100000) {
            throw new InvalidArgumentException('AI proposal response must be non-empty JSON within the size limit.');
        }

        try {
            $payload = json_decode($json, true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('AI proposal response is not valid JSON.', 0, $exception);
        }

        if (!is_array($payload) || array_is_list($payload)) {
            throw new InvalidArgumentException('AI proposal response must be a JSON object.');
        }

        $this->assertExactKeys($payload, self::TOP_LEVEL_KEYS, 'proposal');

        if (!is_array($payload['updates']) || array_is_list($payload['updates'])) {
            throw new InvalidArgumentException('Proposal updates must be an object.');
        }
        $this->assertExactKeys($payload['updates'], self::UPDATE_KEYS, 'proposal updates');

        if (!is_array($payload['updates']['terminology']) || array_is_list($payload['updates']['terminology'])) {
            throw new InvalidArgumentException('Terminology updates must be an object.');
        }
        if (!is_array($payload['updates']['context']) || array_is_list($payload['updates']['context'])) {
            throw new InvalidArgumentException('Context updates must be an object.');
        }
        if (!is_array($payload['updates']['questions']) || !array_is_list($payload['updates']['questions'])) {
            throw new InvalidArgumentException('Question updates must be a list.');
        }
        if (!is_int($payload['confidence']) && !is_float($payload['confidence'])) {
            throw new InvalidArgumentException('Proposal confidence must be numeric.');
        }
        if ((float) $payload['confidence'] < 0.0 || (float) $payload['confidence'] > 1.0) {
            throw new InvalidArgumentException('Proposal confidence must be between 0 and 1.');
        }
        if (!is_string($payload['rationale'])) {
            throw new InvalidArgumentException('Proposal rationale must be a string.');
        }
        if (!is_array($payload['provenance']) || !array_is_list($payload['provenance'])) {
            throw new InvalidArgumentException('Proposal provenance must be a list.');
        }

        return $payload;
    }

    public function validateProposal(array $payload): array
    {
        $updates = $payload['updates'];
        $this->validateTerminology($updates['terminology']);
        $this->validateQuestions($updates['questions']);
        $this->validateProvenance($payload['provenance']);

        if (trim($payload['rationale']) === '' || mb_strlen($payload['rationale']) > 2000) {
            throw new InvalidArgumentException('Proposal rationale must contain between 1 and 2000 characters.');
        }

        foreach (array_keys($updates['context']) as $section) {
            if (in_array($section, ['terminology', 'questions'], true)) {
                throw new InvalidArgumentException("Context section {$section} must use its dedicated proposal field.");
            }
        }

        $values = $updates['context'];
        if ($updates['terminology'] !== []) {
            $values['terminology'] = $updates['terminology'];
        }
        if ($updates['questions'] !== []) {
            $values['questions'] = $updates['questions'];
        }

        VerticalContextLayer::fromArray([
            'id' => 'ai.proposal.validation',
            'kind' => 'tenant',
            'version' => '1.0.0',
            'source' => 'ai.proposal.validation',
            'values' => $values,
            'generated_at' => '1970-01-01T00:00:00+00:00',
        ]);

        return [
            'updates' => [
                'terminology' => $updates['terminology'],
                'context' => $updates['context'],
                'questions' => $updates['questions'],
            ],
            'confidence' => (float) $payload['confidence'],
            'rationale' => $payload['rationale'],
            'provenance' => $payload['provenance'],
        ];
    }

    private function validateTerminology(array $terminology): void
    {
        if (count($terminology) > 100) {
            throw new InvalidArgumentException('Proposal terminology exceeds 100 entries.');
        }

        foreach ($terminology as $key => $label) {
            if (!is_string($key) || preg_match('/^[a-z0-9][a-z0-9._-]{0,127}$/', $key) !== 1) {
                throw new InvalidArgumentException('Terminology keys must use stable semantic identifiers.');
            }
            if (!is_string($label) || trim($label) === '' || mb_strlen($label) > 120) {
                throw new InvalidArgumentException("Terminology label {$key} must contain between 1 and 120 characters.");
            }
        }
    }

    private function validateQuestions(array $questions): void
    {
        if (count($questions) > 50) {
            throw new InvalidArgumentException('Proposal questions exceed 50 entries.');
        }

        $seen = [];
        foreach ($questions as $index => $question) {
            if (!is_array($question) || array_is_list($question)) {
                throw new InvalidArgumentException("Proposal question {$index} must be an object.");
            }
            $this->assertExactKeys($question, ['id', 'prompt', 'type', 'required', 'options'], "proposal question {$index}");

            $id = $question['id'];
            if (!is_string($id) || preg_match('/^[a-z][a-z0-9._-]{0,99}$/', $id) !== 1) {
                throw new InvalidArgumentException("Proposal question {$index} has an invalid ID.");
            }
            if (isset($seen[$id])) {
                throw new InvalidArgumentException("Duplicate proposal question ID: {$id}.");
            }
            $seen[$id] = true;

            if (!is_string($question['prompt']) || trim($question['prompt']) === '' || mb_strlen($question['prompt']) > 500) {
                throw new InvalidArgumentException("Proposal question {$id} prompt must contain between 1 and 500 characters.");
            }
            if (!is_string($question['type']) || !in_array($question['type'], self::QUESTION_TYPES, true)) {
                throw new InvalidArgumentException("Unsupported proposal question type for {$id}.");
            }
            if (!is_bool($question['required'])) {
                throw new InvalidArgumentException("Proposal question {$id} required must be boolean.");
            }
            if (!is_array($question['options']) || !array_is_list($question['options'])) {
                throw new InvalidArgumentException("Proposal question {$id} options must be a list.");
            }
            $this->validateOptions($id, $question['type'], $question['options']);
        }
    }

    private function validateOptions(string $questionId, string $type, array $options): void
    {
        if (count($options) > 50) {
            throw new InvalidArgumentException("Proposal question {$questionId} exceeds 50 options.");
        }
        if (in_array($type, ['single_select', 'multi_select'], true) && $options === []) {
            throw new InvalidArgumentException("Proposal question {$questionId} requires options.");
        }
        if (!in_array($type, ['single_select', 'multi_select'], true) && $options !== []) {
            throw new InvalidArgumentException("Proposal question {$questionId} cannot define options for type {$type}.");
        }

        $values = [];
        foreach ($options as $index => $option) {
            if (!is_array($option) || array_is_list($option)) {
                throw new InvalidArgumentException("Proposal option {$questionId}.{$index} must be an object.");
            }
            $this->assertExactKeys($option, ['value', 'label'], "proposal option {$questionId}.{$index}");
            if (!is_string($option['value']) || trim($option['value']) === '' || mb_strlen($option['value']) > 100) {
                throw new InvalidArgumentException("Proposal option {$questionId}.{$index} has an invalid value.");
            }
            if (isset($values[$option['value']])) {
                throw new InvalidArgumentException("Proposal question {$questionId} has duplicate option values.");
            }
            $values[$option['value']] = true;
            if (!is_string($option['label']) || trim($option['label']) === '' || mb_strlen($option['label']) > 120) {
                throw new InvalidArgumentException("Proposal option {$questionId}.{$index} label must contain between 1 and 120 characters.");
            }
        }
    }

    private function validateProvenance(array $provenance): void
    {
        if ($provenance === [] || count($provenance) > 50) {
            throw new InvalidArgumentException('Proposal provenance must contain between 1 and 50 entries.');
        }

        foreach ($provenance as $index => $entry) {
            if (!is_array($entry) || array_is_list($entry)) {
                throw new InvalidArgumentException("Proposal provenance {$index} must be an object.");
            }
            $this->assertExactKeys($entry, ['source', 'path', 'reason'], "proposal provenance {$index}");
            foreach (['source', 'path', 'reason'] as $field) {
                if (!is_string($entry[$field]) || trim($entry[$field]) === '') {
                    throw new InvalidArgumentException("Proposal provenance {$index}.{$field} cannot be empty.");
                }
            }
            if (mb_strlen($entry['source']) > 250 || mb_strlen($entry['path']) > 250 || mb_strlen($entry['reason']) > 500) {
                throw new InvalidArgumentException("Proposal provenance {$index} exceeds field length limits.");
            }
            if (!str_starts_with($entry['path'], '/')) {
                throw new InvalidArgumentException("Proposal provenance {$index}.path must be a JSON pointer.");
            }
        }
    }

    private function assertExactKeys(array $payload, array $allowed, string $label): void
    {
        $actual = array_keys($payload);
        sort($actual, SORT_STRING);
        $expected = $allowed;
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            throw new InvalidArgumentException("{$label} must contain exactly: " . implode(', ', $allowed) . '.');
        }
    }
}
