<?php

declare(strict_types=1);

namespace TitanAI\Hybrid\Console;

use TitanAI\Hybrid\Memory\Services\UnifiedMemoryRepository;
use Illuminate\Console\Command;

final class PurgeExpiredMemoriesCommand extends Command
{
    protected $signature = 'titanai:memory:purge';

    protected $description = 'Delete expired records from shared TitanAI memory.';

    public function handle(UnifiedMemoryRepository $memory): int
    {
        $deleted = $memory->purgeExpired();
        $this->info("Purged {$deleted} expired TitanAI memory record(s).");

        return self::SUCCESS;
    }
}
