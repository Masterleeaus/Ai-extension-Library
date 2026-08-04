<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Services;

use App\Domains\Entity\Enums\EntityEnum;

class AIPhotoshootProviderFactory
{
    public function make(): AIPhotoshootProviderInterface
    {
        return app(AIPhotoshootFalService::class);
    }

    /**
     * The EntityEnum the current provider maps to. Used by the base
     * controller for credit calculation via `Entity::driver(...)`.
     */
    public function entityEnum(): EntityEnum
    {
        return $this->make()->entityEnum();
    }
}
