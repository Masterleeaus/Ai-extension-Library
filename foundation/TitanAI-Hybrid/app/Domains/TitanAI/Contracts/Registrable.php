<?php

namespace App\Domains\TitanAI\Contracts;

/**
 * Registrable Contract
 * 
 * Defines the interface for components that can be registered
 * in the UnifiedRegistry (skills, actions, connectors, tools).
 */
interface Registrable
{
    /**
     * Get the unique key for this component.
     * 
     * @return string
     */
    public function key(): string;

    /**
     * Get the human-readable name.
     * 
     * @return string
     */
    public function name(): string;

    /**
     * Get the component description.
     * 
     * @return string
     */
    public function description(): string;

    /**
     * Get component metadata as array.
     * 
     * @return array
     */
    public function metadata(): array;
}
