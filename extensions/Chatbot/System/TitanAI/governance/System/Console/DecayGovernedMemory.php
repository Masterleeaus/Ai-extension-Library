<?php

declare(strict_types=1);
namespace App\Extensions\TitanAIGovernance\System\Console;
use Illuminate\Console\Command;
use App\Extensions\TitanAIGovernance\System\Memory\MemoryDecayService;
final class DecayGovernedMemory extends Command { protected $signature='titan-ai:memory-decay {--tenant=}'; protected $description='Expire and decay governed AI memory.'; public function handle(MemoryDecayService $service): int { $result=$service->run($this->option('tenant')!==null?(int)$this->option('tenant'):null); $this->info(json_encode($result)); return self::SUCCESS; } }
