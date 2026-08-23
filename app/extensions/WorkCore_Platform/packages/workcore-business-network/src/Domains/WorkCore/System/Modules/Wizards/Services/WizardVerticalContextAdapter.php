<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardVerticalContextRepositoryContract;
use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;
use TitanZero\Interaction\Vertical\DTO\VerticalContextSnapshot;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

final class WizardVerticalContextAdapter
{
    private const ALLOWED_SOURCE_TYPES = [
        'system_default',
        'vertical_pack',
        'existing_company_data',
        'imported',
        'user_entered',
        'ai_extracted',
        'ai_suggested',
    ];

    private const ALLOWED_RISKS = ['low', 'medium', 'high', 'critical'];

    private const CONNECTION_KEYS = ['status', 'provider_reference', 'connection_id', 'verified_at'];

    public function __construct(
        private readonly WizardVerticalContextRepositoryContract $repository,
        private readonly VerticalContextComposer $composer,
    ) {}

    public function compose(string $runPublicId, int $companyId, array $actor): VerticalContextSnapshot
    {
        $snapshot = $this->repository->snapshot($runPublicId, $companyId);
        $run = $snapshot['run'];
        $definition = $snapshot['definition_snapshot'];
        $questions = $this->questionIndex($definition);
        $answerRevision = (int) $snapshot['answer_revision'];
        $metadata = (array) ($run['metadata'] ?? []);

        $layers = [$this->runLayer($run, $metadata)];
        $answerValues = [];

        foreach ((array) $snapshot['answers'] as $answer) {
            $questionKey = (string) $answer['question_key'];
            $question = $questions[$questionKey] ?? ['key' => $questionKey, 'section_key' => null];
            $answerValues[$questionKey] = $answer['value'];

            if ($this->isSecureAnswer($question, $answer['value'] ?? null, $questionKey)) {
                $layers[] = $this->secureConnectionLayer($answer, $question, $answerRevision);
                continue;
            }

            $layers[] = $this->answerLayer($answer, $question, $answerRevision);
        }

        $locale = [
            'country' => $answerValues['company.country_code'] ?? null,
            'language' => $answerValues['company.locale'] ?? null,
            'timezone' => $answerValues['company.timezone'] ?? null,
            'currency' => $answerValues['company.currency_code'] ?? null,
        ];

        return $this->composer->compose($layers, [
            'company_id' => $companyId,
            'wizard' => [
                'run_id' => (string) $run['public_id'],
                'definition_key' => (string) $run['definition_key'],
                'definition_version' => (int) $run['definition_version'],
                'answer_revision' => $answerRevision,
                'status' => (string) $run['status'],
                'mode' => (string) $run['mode'],
                'current_section_key' => $run['current_section_key'],
                'current_question_key' => $run['current_question_key'],
                'launch_phase' => (string) ($metadata['launch_phase'] ?? $run['current_section_key'] ?? 'business_profile'),
            ],
            'actor' => $actor,
            'locale' => array_filter($locale, static fn (mixed $value): bool => $value !== null && $value !== ''),
            'approvals' => (array) $snapshot['approved_sections'],
            'unanswered' => $this->unansweredRequiredQuestions($questions, array_keys($answerValues)),
        ]);
    }

    private function runLayer(array $run, array $metadata): VerticalContextLayer
    {
        return VerticalContextLayer::fromArray([
            'id' => 'wizard.run.' . $this->safeId((string) $run['public_id']),
            'kind' => 'tenant',
            'version' => '1.0.0',
            'source' => 'wizard-run:' . $run['public_id'],
            'values' => [
                'capabilities' => [
                    'vertical_family' => $metadata['vertical_family'] ?? null,
                    'subtype' => $metadata['subtype'] ?? null,
                    'selected' => array_values((array) ($metadata['capabilities'] ?? [])),
                ],
                'activation' => [
                    'launch_phase' => $metadata['launch_phase'] ?? $run['current_section_key'] ?? 'business_profile',
                    'run_status' => $run['status'],
                ],
            ],
            'provenance' => [
                'source' => 'wizard-run:' . $run['public_id'],
                'source_type' => 'existing_company_data',
                'confidence' => 1.0,
                'confirmed' => true,
                'risk' => 'low',
                'revision' => (int) ($metadata['answer_revision'] ?? 0),
            ],
        ]);
    }

    private function answerLayer(array $answer, array $question, int $answerRevision): VerticalContextLayer
    {
        $questionKey = (string) $answer['question_key'];
        return VerticalContextLayer::fromArray([
            'id' => 'wizard.answer.' . $this->safeId((string) $answer['public_id']),
            'kind' => 'tenant',
            'version' => '1.0.0',
            'source' => 'wizard-answer:' . $questionKey,
            'values' => [
                'questions' => [
                    'answers' => [
                        $questionKey => [
                            'value' => $answer['value'],
                            'section' => $question['section_key'] ?? null,
                            'target_owner' => $question['target_owner'] ?? null,
                            'response_type' => $question['response_type'] ?? null,
                            'handling' => $question['handling'] ?? null,
                        ],
                    ],
                ],
            ],
            'provenance' => [
                'source' => 'wizard-answer:' . $questionKey,
                'source_type' => $this->sourceType((string) ($answer['source'] ?? 'existing_company_data')),
                'confidence' => $this->confidence($answer),
                'confirmed' => (bool) ($answer['is_confirmed'] ?? false),
                'risk' => $this->risk($question),
                'revision' => $answerRevision,
            ],
        ]);
    }

    private function secureConnectionLayer(array $answer, array $question, int $answerRevision): VerticalContextLayer
    {
        $questionKey = (string) $answer['question_key'];
        $value = is_array($answer['value']) ? $answer['value'] : [];
        $connection = [];
        foreach (self::CONNECTION_KEYS as $key) {
            if (array_key_exists($key, $value) && $value[$key] !== null && $value[$key] !== '') {
                $connection[$key] = $value[$key];
            }
        }
        if (!isset($connection['status'])) {
            $connection['status'] = 'not_connected';
        }

        return VerticalContextLayer::fromArray([
            'id' => 'wizard.secure.' . $this->safeId((string) $answer['public_id']),
            'kind' => 'tenant',
            'version' => '1.0.0',
            'source' => 'wizard-answer:' . $questionKey,
            'values' => [
                'activation' => [
                    'secure_connections' => [
                        $questionKey => $connection,
                    ],
                ],
            ],
            'provenance' => [
                'source' => 'wizard-answer:' . $questionKey,
                'source_type' => $this->sourceType((string) ($answer['source'] ?? 'existing_company_data')),
                'confidence' => $this->confidence($answer),
                'confirmed' => (bool) ($answer['is_confirmed'] ?? false),
                'risk' => $this->risk($question),
                'revision' => $answerRevision,
            ],
        ]);
    }

    private function questionIndex(array $definition): array
    {
        $index = [];
        foreach ((array) ($definition['sections'] ?? []) as $section) {
            $sectionKey = (string) ($section['key'] ?? '');
            foreach ((array) ($section['questions'] ?? []) as $question) {
                if (!is_array($question) || empty($question['key'])) {
                    continue;
                }
                $question['section_key'] = $sectionKey;
                $index[(string) $question['key']] = $question;
            }
        }
        return $index;
    }

    private function unansweredRequiredQuestions(array $questions, array $answeredKeys): array
    {
        $answered = array_fill_keys($answeredKeys, true);
        $unanswered = [];
        foreach ($questions as $key => $question) {
            if ((bool) ($question['required'] ?? false) && !isset($answered[$key])) {
                $unanswered[] = $key;
            }
        }
        sort($unanswered, SORT_STRING);
        return $unanswered;
    }

    private function isSecureAnswer(array $question, mixed $value, string $questionKey): bool
    {
        $handling = strtolower((string) ($question['handling'] ?? ''));
        $responseType = strtolower((string) ($question['response_type'] ?? ''));
        $owner = strtolower((string) ($question['target_owner'] ?? ''));

        return in_array($handling, ['secure_task', 'secret_connection'], true)
            || in_array($responseType, ['secret_connection', 'secure_form', 'connection_task'], true)
            || str_contains($owner, 'vault')
            || str_contains($owner, 'credential')
            || str_contains($owner, 'secret')
            || preg_match('/(?:credential|password|passphrase|api[_-]?key|token|private[_-]?key|client[_-]?secret)/i', $questionKey) === 1
            || $this->containsSensitiveKey($value);
    }

    private function containsSensitiveKey(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $key => $nested) {
            $normalized = strtolower(str_replace(['-', ' '], '_', (string) $key));
            if (in_array($normalized, [
                'password', 'passphrase', 'secret', 'api_key', 'apikey', 'api_token',
                'access_token', 'refresh_token', 'private_key', 'client_secret',
                'service_account', 'service_account_json', 'bank_account', 'account_number',
            ], true)) {
                return true;
            }
            if ($this->containsSensitiveKey($nested)) {
                return true;
            }
        }

        return false;
    }

    private function sourceType(string $source): string
    {
        $source = strtolower(trim($source));
        if (in_array($source, self::ALLOWED_SOURCE_TYPES, true)) {
            return $source;
        }
        return match ($source) {
            'ai', 'ai_generated', 'ai_inferred' => 'ai_extracted',
            'manual', 'user' => 'user_entered',
            default => 'existing_company_data',
        };
    }

    private function confidence(array $answer): float
    {
        if ($answer['confidence'] !== null) {
            return max(0.0, min(1.0, (float) $answer['confidence']));
        }
        return $this->sourceType((string) ($answer['source'] ?? '')) === 'user_entered' ? 1.0 : 0.5;
    }

    private function risk(array $question): string
    {
        $risk = strtolower((string) ($question['risk'] ?? ''));
        if (in_array($risk, self::ALLOWED_RISKS, true)) {
            return $risk;
        }
        return match (strtolower((string) ($question['approval_gate'] ?? ''))) {
            'critical' => 'critical',
            'high' => 'high',
            'section' => 'medium',
            default => 'low',
        };
    }

    private function safeId(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?? '';
        return trim($safe, '-') !== '' ? trim($safe, '-') : substr(hash('sha256', $value), 0, 24);
    }
}
