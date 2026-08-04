<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class PerformanceMetricsAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'performance-metrics-agent'; }
    public static function name(): string { return 'Performance Metrics Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Track performance metrics across all dimensions',
            'Calculate KPIs: conversion rates, completion rates',
            'Generate comprehensive reports and dashboards',
            'Compare against benchmarks and goals',
            'Support multiple time periods (daily, weekly, monthly)',
            'Export reports in multiple formats (PDF, Excel, CSV)',
            'Provide insights and recommendations',
            'Identify trends and patterns',
            'Track team and individual performance'
        ];
    }
    public static function permissions(): array
    {
        return [
            'jobs.read',
            'analytics.read',
            'analytics.write',
            'reports.generate',
            'dashboards.read'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'metrics.track','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $period = $input->period ?? 'monthly';
        
        $result = $this->runtime->executeTool('metrics.track', ['period' => $period], $context);
        return AgentActionResult::fromTool($result, 'metrics.tracked', []);
    }
}
