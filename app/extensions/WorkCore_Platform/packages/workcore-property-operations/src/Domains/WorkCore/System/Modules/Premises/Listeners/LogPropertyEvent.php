<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Premises\Listeners;

class LogPropertyEvent
{
    public function handle($event): void
    {
        // Intentionally light-weight. Hook into your platform activity log later.
    }
}
