<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Utils;

use App\Domains\Entity\Enums\EntityEnum;
use App\Models\GatewayProducts;
use App\Models\Gateways;
use App\Models\Plan;
use App\Models\SettingTwo;
use App\Services\GatewaySelector;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription as Subscriptions;
use RuntimeException;
use Throwable;

class MigrationHelpers
{
    public static function processMigration(?string $sqlFilePath, string $table, callable $processCallback, ?string $envFilePath = null): string
    {
        static $sqlContent = null;
        if ($sqlContent === null) {
            $sqlContent = file_get_contents($sqlFilePath);
        }
        $schemaColumns = SqlSchemaParser::extractTableColumns($sqlContent, $table);
        if (empty($schemaColumns)) {
            throw new RuntimeException("No columns found for {$table} in the SQL file.");
        }
        $insertData = self::extractInsertStatements($sqlContent, $table);
        if (empty($insertData)) {
            Log::info("No records to migrate for table {$table}");
            return "No records found for {$table}";
        }
        $inserted = 0;
        try {
            foreach ($insertData as $data) {
                $columns = $data['columns'] ?? $schemaColumns;
                $values = $data['values'];
                if (count($columns) !== count($values)) {
                    Log::error("{$table} migration failed: column count mismatch", [
                        'expected' => count($columns), 'actual' => count($values),
                        'columns' => $columns, 'values' => $values,
                    ]);
                    continue;
                }
                $record = array_combine($columns, $values);
                if (! $record) {
                    Log::error("{$table} migration failed: could not combine columns and values", [
                        'columns' => $columns, 'values' => $values,
                    ]);
                    continue;
                }
                $env = ! empty($envFilePath) ? self::convertEnvToArr($envFilePath) : null;
                if ($processCallback($record, $env)) {
                    $inserted++;
                }
            }
        } catch (Throwable $e) {
            Log::critical("Migration error on table {$table}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return "{$table} migrated successfully: {$inserted} record(s)";
    }

    private static function extractInsertStatements(string $sqlContent, string $table): array
    {
        $insertData = [];
        $patternWithColumns = "/INSERT\s+INTO\s+`{$table}`\s*\(([^)]+)\)\s+VALUES\s*(.*?);/is";
        $patternWithoutColumns = "/INSERT\s+INTO\s+`{$table}`\s+VALUES\s*(.*?);/is";
        if (preg_match_all($patternWithColumns, $sqlContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columns = self::parseColumns(trim($match[1]));
                foreach (self::parseValues(trim($match[2])) as $values) {
                    $insertData[] = ['columns' => $columns, 'values' => $values];
                }
            }
        }
        if (preg_match_all($patternWithoutColumns, $sqlContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                foreach (self::parseValues(trim($match[1])) as $values) {
                    $insertData[] = ['columns' => null, 'values' => $values];
                }
            }
        }

        return $insertData;
    }

    private static function parseColumns(string $columnsPart): array
    {
        $columns = [];
        $parts = preg_split('/,(?=(?:[^`]*`[^`]*`)*[^`]*$)/', trim($columnsPart));
        foreach ($parts as $part) {
            $column = trim($part, " \t\n\r\0\x0B`");
            if ($column !== '') {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private static function parseValues(string $valuesPart): array
    {
        $allValues = [];
        if (preg_match_all('/\(([^)]*(?:\([^)]*\)[^)]*)*)\)/', trim($valuesPart), $matches)) {
            foreach ($matches[1] as $valuesRow) {
                $allValues[] = SqlValueParser::parseRow($valuesRow);
            }
        }

        return $allValues;
    }

    public static function normalizeModelName(string $value): string
    {
        return str_replace(['_', '-'], '', strtolower($value));
    }

    public static function processCredits(?array $credits, ?array $normalizedCredits): array
    {
        if (empty($credits)) {
            return [];
        }
        foreach ($credits as &$engineModels) {
            foreach ($engineModels as $modelName => &$modelData) {
                $normalizedModelName = self::normalizeModelName($modelName);
                if (array_key_exists($normalizedModelName, $normalizedCredits)) {
                    $value = $normalizedCredits[$normalizedModelName];
                    $modelData['isUnlimited'] = ((int) $value) === -1;
                    $modelData['credit'] = max((int) $value, 0);
                }
            }
        }

        return $credits;
    }

    public static function createGatewayProducts(?array $record, ?Plan $plan): void
    {
        $record ??= [];
        $gateways = [
            'stripe' => ['productId' => $record['stripe_gateway_plan_id'] ?? null, 'gateway_title' => 'Stripe'],
            'paypal' => ['productId' => $record['paypal_gateway_plan_id'] ?? null, 'gateway_title' => 'PayPal'],
            'paystack' => ['productId' => $record['paystack_gateway_plan_id'] ?? null, 'gateway_title' => 'Paystack'],
            'razorpay' => ['productId' => $record['razorpay_gateway_plan_id'] ?? null, 'gateway_title' => 'Razorpay'],
        ];
        foreach ($gateways as $key => $gateway) {
            if (! empty($gateway['productId'])) {
                $product = new GatewayProducts;
                $product->plan_id = $plan?->id;
                $product->plan_name = $plan?->name;
                $product->gateway_code = $key;
                $product->gateway_title = $gateway['gateway_title'];
                $product->product_id = $gateway['productId'];
                $product->save();
            }
        }
    }

    public static function getPlansPriceIds(?Gateways $gateway): void
    {
        try {
            if ($gateway) {
                GatewaySelector::selectGateway($gateway->code)::getPlansPriceIdsForMigration();
            }
        } catch (Throwable) {
        }
    }

    public static function getUsersCustomerIds(?Gateways $gateway, ?Subscriptions $subscription): void
    {
        try {
            if ($gateway && $subscription) {
                GatewaySelector::selectGateway($gateway->code)::getUsersCustomerIdsForMigration($subscription);
            }
        } catch (Throwable) {
        }
    }

    public static function convertEnvToArr(string $envFilePath): array
    {
        $lines = explode("\n", file_get_contents($envFilePath));
        $env = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            $env[trim($key)] = $value;
        }

        return $env;
    }

    public static function defaultWordModel(): EntityEnum
    {
        return match (setting('default_ai_engine')) {
            'openai' => EntityEnum::GPT_5_MINI,
            'anthropic' => EntityEnum::fromSlug(setting('anthropic_default_model')),
            'gemini' => EntityEnum::fromSlug(setting('gemini_default_model')),
            default => EntityEnum::GPT_5_MINI,
        };
    }

    public static function defaultImageModels(): array
    {
        $settingsTwo = SettingTwo::getCache();
        $models = [];
        $dalleModel = match ($settingsTwo?->dalle) {
            'dalle3' => EntityEnum::DALL_E_3->slug(),
            'dalle2' => EntityEnum::DALL_E_2->slug(),
            default => $settingsTwo?->dalle ?? EntityEnum::DALL_E_2->slug(),
        };
        if (! setting('dalle_hidden') && $openAIModel = EntityEnum::fromSlug($dalleModel)) {
            $models['openai'] = $openAIModel;
        }
        $stableModel = $settingsTwo?->stablediffusion_default_model ?? EntityEnum::STABLE_DIFFUSION_XL_1024_V_1_0->slug();
        if (! setting('stable_hidden') && $stableModel = EntityEnum::fromSlug($stableModel)) {
            $models['stable'] = $stableModel;
        }
        $falModel = setting('fal_ai_default_model', 'flux-realism');
        if ($falModel = EntityEnum::fromSlug($falModel)) {
            $models['fal'] = $falModel;
        }

        return $models;
    }
}
