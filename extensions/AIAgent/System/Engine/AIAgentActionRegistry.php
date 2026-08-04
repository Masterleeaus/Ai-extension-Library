<?php

declare(strict_types=1);

namespace App\Extensions\AIAgent\System\Engine;

use App\Extensions\AIAgent\System\Actions\Contracts\ActionInterface;
use App\Extensions\AIAgent\System\Actions\Contracts\AIAgentActionInterface;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use LogicException;

class AIAgentActionRegistry
{
    /** @var array<string, class-string<ActionInterface>> */
    private array $actions = [];

    /** @var array<int, array{key: string, label: string}> */
    private array $failed = [];

    /** @var list<callable(string, class-string<ActionInterface>): void> */
    private array $registeredListeners = [];

    /** @var array<string,true> */
    private array $registeredListenerKeys = [];

    /**
     * Register an action class under a stable key.
     *
     * Re-registering the same key/class pair is idempotent. Replacing an
     * existing key with another class is rejected so native and unified
     * registries cannot silently drift apart.
     *
     * @param class-string<ActionInterface> $actionClass
     */
    public function register(string $key, string $actionClass): void
    {
        $key = trim($key);
        if ($key === '') {
            throw new InvalidArgumentException('AI Agent action key cannot be empty.');
        }
        if (! is_a($actionClass, ActionInterface::class, true)) {
            throw new InvalidArgumentException("AI Agent action [{$actionClass}] must implement " . ActionInterface::class . '.');
        }

        $existing = $this->actions[$key] ?? null;
        if ($existing === $actionClass) {
            return;
        }
        if ($existing !== null) {
            throw new LogicException("AI Agent action [{$key}] is already registered by [{$existing}].");
        }

        $this->actions[$key] = $actionClass;
        foreach ($this->registeredListeners as $listener) {
            $listener($key, $actionClass);
        }
    }

    /**
     * Observe action registration. Replay lets a late unified-registry bridge
     * mirror actions that were registered earlier in provider boot.
     *
     * @param callable(string, class-string<ActionInterface>): void $listener
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
            foreach ($this->actions as $key => $actionClass) {
                $listener($key, $actionClass);
            }
        }
    }

    /** @return array<string, class-string<ActionInterface>> */
    public function registeredClasses(): array
    {
        return $this->actions;
    }

    /**
     * Resolve an action instance by key.
     *
     * @throws Exception
     */
    public function resolve(string $key): ActionInterface
    {
        if (! isset($this->actions[$key])) {
            throw new Exception("AIAgentActionRegistry: action [{$key}] is not registered.");
        }

        return app($this->actions[$key]);
    }

    /**
     * Return actions that failed to resolve due to missing dependencies.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function failed(): array
    {
        return $this->failed;
    }

    /**
     * Return all registered actions grouped by category.
     * Each entry includes metadata if the action implements AIAgentActionInterface.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function all(): array
    {
        $this->failed = [];
        $grouped = [];

        foreach ($this->actions as $key => $class) {
            try {
                $instance = app($class);
            } catch (BindingResolutionException) {
                $this->failed[] = [
                    'key'   => $key,
                    'label' => (string) str(class_basename($class))->replaceLast('Action', '')->headline(),
                ];

                continue;
            }

            if ($instance instanceof AIAgentActionInterface) {
                $category = $instance->getCategory();

                $grouped[$category][] = [
                    'key'           => $key,
                    'label'         => $instance->getLabel(),
                    'description'   => $instance->getDescription(),
                    'icon'          => $instance->getIcon(),
                    'category'      => $category,
                    'config_schema' => $instance->getConfigSchema(),
                ];
            } else {
                $grouped['utilities'][] = [
                    'key'           => $key,
                    'label'         => $key,
                    'description'   => '',
                    'icon'          => 'tabler-bolt',
                    'category'      => 'utilities',
                    'config_schema' => [],
                ];
            }
        }

        return $grouped;
    }

    /** @return array<int, array<string, mixed>> */
    public function byCategory(string $category): array
    {
        return $this->all()[$category] ?? [];
    }

    /** @return string[] */
    public function keys(): array
    {
        return array_keys($this->actions);
    }
}
