<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

class EnvironmentValidator
{
    private array $errors = [];
    private array $warnings = [];

    public static function validate(): self
    {
        return new static();
    }

    public function check(): self
    {
        $this->checkRequired();
        $this->checkTypes();
        $this->checkSecurity();

        return $this;
    }

    private function checkRequired(): void
    {
        $required = [
            'APP_NAME' => 'Application name',
            'APP_ENV' => 'Environment (local|testing|staging|production)',
            'APP_KEY' => 'Encryption key',
            'DB_CONNECTION' => 'Database connection',
            'DB_HOST' => 'Database host',
            'DB_DATABASE' => 'Database name',
        ];

        foreach ($required as $key => $description) {
            if (!env($key)) {
                $this->errors[] = "Missing required env var: {$key} ({$description})";
            }
        }
    }

    private function checkTypes(): void
    {
        $checks = [
            'DB_PORT' => 'integer',
            'APP_DEBUG' => 'boolean',
            'MULTITENANT_ENABLED' => 'boolean',
            'MULTITENANT_STRICT_MODE' => 'boolean',
            'API_RATE_LIMIT_PER_MINUTE' => 'integer',
        ];

        foreach ($checks as $key => $expectedType) {
            $value = env($key);
            if ($value === null) {
                continue;
            }

            if (!$this->isValidType($value, $expectedType)) {
                $this->errors[] = "Invalid type for {$key}: expected {$expectedType}, got " . gettype($value);
            }
        }
    }

    private function checkSecurity(): void
    {
        // Check for hardcoded secrets
        if (env('APP_DEBUG') && env('APP_ENV') === 'production') {
            $this->warnings[] = 'APP_DEBUG is enabled in production environment!';
        }

        if (!env('APP_KEY')) {
            $this->errors[] = 'APP_KEY is not set - encryption will fail';
        }

        if (env('APP_ENV') === 'production' && !env('SENTRY_DSN')) {
            $this->warnings[] = 'SENTRY_DSN not configured in production - error tracking disabled';
        }
    }

    private function isValidType(mixed $value, string $type): bool
    {
        return match ($type) {
            'integer' => is_numeric($value) && intval($value) == $value,
            'boolean' => in_array(strtolower((string)$value), ['true', 'false', '1', '0', 'yes', 'no']),
            'string' => is_string($value),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            default => true,
        };
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function report(): void
    {
        if ($this->hasErrors()) {
            Log::critical('Environment validation failed', [
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ]);

            if (!app()->isProduction()) {
                dd($this->errors);
            }

            throw new \RuntimeException('Environment validation failed: ' . implode(', ', $this->errors));
        }

        if (!empty($this->warnings)) {
            Log::warning('Environment validation warnings', $this->warnings);
        }
    }
}
