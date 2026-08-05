<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\PromptCustomizationContract;
use PDO;
use Foundation\Support\JsonHelper;

class PromptCustomization implements PromptCustomizationContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'prompt_customization_';
    private const TABLE_PROMPTS = self::TABLE_PREFIX . 'prompts';
    private const TABLE_TEST_RESULTS = self::TABLE_PREFIX . 'test_results';
    private string $tablePrefix = self::TABLE_PREFIX;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createPrompt(
        string $tenantId,
        string $promptName,
        string $systemPrompt,
        array $metadata = []
    ): string {
        $promptId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_PROMPTS . " (id, tenant_id, name, system_prompt, metadata, version, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $promptId,
            $tenantId,
            $promptName,
            $systemPrompt,
            json_encode($metadata),
            1,
            DateTimeHelper::now(),
        ]);

        return $promptId;
    }

    public function getPrompt(
        string $tenantId,
        string $promptId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_PROMPTS . " WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$promptId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['metadata'] = JsonHelper::decode($result['metadata']);
        }

        return $result ?: null;
    }

    public function updatePrompt(
        string $tenantId,
        string $promptId,
        string $systemPrompt
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_PROMPTS . " SET system_prompt = ?, version = version + 1, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$systemPrompt, DateTimeHelper::now(), $promptId, $tenantId]);
    }

    public function testPrompt(
        string $tenantId,
        string $promptId,
        string $userInput
    ): array {
        $prompt = $this->getPrompt($tenantId, $promptId);

        if (!$prompt) {
            return ['success' => false, 'error' => 'Prompt not found'];
        }

        $testId = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_TEST_RESULTS . " (id, prompt_id, tenant_id, user_input, output, tested_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $mockOutput = "Test output for: {$userInput}";
        $stmt->execute([$testId, $promptId, $tenantId, $userInput, $mockOutput, DateTimeHelper::now()]);

        return [
            'success' => true,
            'test_id' => $testId,
            'input' => $userInput,
            'output' => $mockOutput,
        ];
    }

    public function publishPrompt(
        string $tenantId,
        string $promptId,
        string $version
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE " . self::TABLE_PROMPTS . " SET published = 1, published_version = ?, published_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$version, DateTimeHelper::now(), $promptId, $tenantId]);
    }

    public function listPrompts(
        string $tenantId,
        ?string $category = null
    ): array {
        $query = "SELECT id, name, version, published, created_at FROM " . self::TABLE_PROMPTS . " WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($category) {
            $query .= " AND JSON_EXTRACT(metadata, '$.category') = ?";
            $params[] = $category;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clonePrompt(
        string $tenantId,
        string $sourcePromptId,
        string $newPromptName
    ): string {
        $source = $this->getPrompt($tenantId, $sourcePromptId);

        if (!$source) {
            throw new \RuntimeException("Source prompt not found");
        }

        return $this->createPrompt(
            $tenantId,
            $newPromptName,
            $source['system_prompt'],
            $source['metadata']
        );
    }

    public function deletePrompt(
        string $tenantId,
        string $promptId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM " . self::TABLE_PROMPTS . " WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([$promptId, $tenantId]);
    }
}
