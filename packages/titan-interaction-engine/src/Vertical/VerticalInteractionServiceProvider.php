<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical;

use Illuminate\Support\ServiceProvider;

final class VerticalInteractionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GenericVerticalBaseProvider::class);
        $this->app->singleton(VerticalContextComposer::class, fn ($app): VerticalContextComposer => new VerticalContextComposer(
            $app->make(GenericVerticalBaseProvider::class),
        ));
    }
}
