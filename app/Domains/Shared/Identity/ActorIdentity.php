<?php

namespace App\Domains\Shared\Identity;

class ActorIdentity
{
    public enum ActorType: string
    {
        case USER = 'USER';
        case SERVICE = 'SERVICE';
        case WEBHOOK = 'WEBHOOK';
        case SCHEDULED_JOB = 'SCHEDULED_JOB';
        case SYSTEM = 'SYSTEM';
    }

    public function __construct(
        private ActorType $type,
        private string $id,
        private ?string $displayName = null,
        private array $permissions = [],
        private \DateTimeImmutable $timestamp = new \DateTimeImmutable()
    ) {}

    public function getType(): ActorType
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public static function forUser($user): self
    {
        return new self(
            ActorType::USER,
            (string) $user->id,
            $user->name ?? $user->email,
            $user->getPermissions() ?? [],
            new \DateTimeImmutable()
        );
    }

    public static function forService(string $serviceName, array $permissions = []): self
    {
        return new self(
            ActorType::SERVICE,
            $serviceName,
            $serviceName,
            $permissions,
            new \DateTimeImmutable()
        );
    }

    public static function forWebhook(string $webhookName, array $permissions = []): self
    {
        return new self(
            ActorType::WEBHOOK,
            $webhookName,
            $webhookName,
            $permissions,
            new \DateTimeImmutable()
        );
    }

    public static function forScheduledJob(string $jobName, array $permissions = []): self
    {
        return new self(
            ActorType::SCHEDULED_JOB,
            $jobName,
            $jobName,
            $permissions,
            new \DateTimeImmutable()
        );
    }

    public static function forSystem(array $permissions = []): self
    {
        return new self(
            ActorType::SYSTEM,
            'SYSTEM',
            'System',
            $permissions,
            new \DateTimeImmutable()
        );
    }
}
