<?php

declare(strict_types=1);

namespace App\Extensions\SocialMediaAutomation\System\Console\Commands;

use App\Extensions\SocialMediaAutomation\System\Models\PendingAutomation;
use Illuminate\Console\Command;

class CleanupPendingAutomationsCommand extends Command
{
    protected $signature = 'social-media-automation:cleanup-pending';

    protected $description = 'Delete old completed and failed pending automation records';

    public function handle(): void
    {
        PendingAutomation::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where('updated_at', '<', now()->subDays(7))
            ->delete();
    }
}
