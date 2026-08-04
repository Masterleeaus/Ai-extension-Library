<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\Connectors;

<<<<<<< HEAD
=======
use InvalidArgumentException;
use LogicException;

>>>>>>> update-extensions-review-upgrade-nvbncq
/**
 * Runtime registry of provider connector extensions.
 *
 * Bound as a singleton by AIChatProServiceProvider. Each provider extension
 * registers itself in its own boot() method, guarded by class_exists() so the
 * host extension stays optional.
 */
class ConnectorRegistry
{
    /** @var array<string, class-string<ConnectorDefinition>> */
    private array $providers = [];

<<<<<<< HEAD
    public function register(string $key, string $definitionClass): void
    {
        $this->providers[$key] = $definitionClass;
=======
    /** @var list<callable(string, class-string<ConnectorDefinition>): void> */
    private array $registeredListeners = [];

    /** @var array<string,true> */
    private array $registeredListenerKeys = [];

    public function register(string $key, string $definitionClass): void
    {
        $key = trim($key);
        if ($key === '') {
            throw new InvalidArgumentException('AIChatPro connector key cannot be empty.');
        }
        if (! is_a($definitionClass, ConnectorDefinition::class, true)) {
            throw new InvalidArgumentException(
                "AIChatPro connector [{$definitionClass}] must implement " . ConnectorDefinition::class . '.',
            );
        }

        $existing = $this->providers[$key] ?? null;
        if ($existing === $definitionClass) {
            return;
        }
        if ($existing !== null) {
            throw new LogicException("AIChatPro connector [{$key}] is already registered by [{$existing}].");
        }

        $this->providers[$key] = $definitionClass;

        foreach ($this->registeredListeners as $listener) {
            $listener($key, $definitionClass);
        }
    }

    /**
     * Observe connector registrations. Replay makes late host listeners see
     * providers that registered before the host service provider booted.
     *
     * @param callable(string, class-string<ConnectorDefinition>): void $listener
     */
    public function onRegistered(
        callable $listener,
        bool $replay = true,
        ?string $listenerKey = null,
    ): void {
        $listenerKey = $listenerKey !== null ? trim($listenerKey) : null;
        if ($listenerKey !== null && $listenerKey !== '' && isset($this->registeredListenerKeys[$listenerKey])) {
            return;
        }
        if ($listenerKey !== null && $listenerKey !== '') {
            $this->registeredListenerKeys[$listenerKey] = true;
        }
        $this->registeredListeners[] = $listener;

        if ($replay) {
            foreach ($this->providers as $key => $definitionClass) {
                $listener($key, $definitionClass);
            }
        }
    }

    /** @return array<string, class-string<ConnectorDefinition>> */
    public function registeredClasses(): array
    {
        return $this->providers;
>>>>>>> update-extensions-review-upgrade-nvbncq
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function get(string $key): ?ConnectorDefinition
    {
        $class = $this->providers[$key] ?? null;

        return $class ? app($class) : null;
    }

    /**
     * @return array<string, ConnectorDefinition>
     */
    public function all(): array
    {
        $resolved = [];

        foreach ($this->providers as $key => $class) {
            $resolved[$key] = app($class);
        }

        return $resolved;
    }

    /**
     * @return array<string, ConnectorDefinition>
     */
    public function enabled(): array
    {
        return array_filter($this->all(), fn (ConnectorDefinition $definition): bool => $definition->isEnabled());
    }

    /**
     * Find the definition for a given tool function name by asking each provider.
     */
    public function findByFunctionName(string $functionName): ?ConnectorDefinition
    {
        foreach ($this->all() as $definition) {
            if (str_starts_with($functionName, 'connector_' . $definition->key() . '_')) {
                return $definition;
            }
        }

        return null;
    }
}
