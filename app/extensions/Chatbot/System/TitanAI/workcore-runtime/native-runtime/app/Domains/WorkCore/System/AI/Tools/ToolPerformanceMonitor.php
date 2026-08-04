<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\AI\Tools;

use App\Domains\WorkCore\System\AI\DTO\AiToolResult;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Log;

final class ToolPerformanceMonitor
{
    private const TTL=86400; private const PREFIX='workcore_ai_tool_metrics:'; private const INDEX=self::PREFIX.'index'; private const SLOW_MS=5000;
    public function __construct(private Repository $cache) {}
    public function recordExecution(string $toolName,int $latencyMs,bool $success): void
    { $key=self::PREFIX.hash('sha256',$toolName);$m=$this->cache->get($key,$this->defaults($toolName));$m['executions']++;$m['total_latency_ms']+=$latencyMs;$m[$success?'successes':'failures']++;if($latencyMs>self::SLOW_MS)$m['slow_executions']++;$m['avg_latency_ms']=round($m['total_latency_ms']/$m['executions'],2);$m['last_execution_at']=date(DATE_ATOM);$this->cache->put($key,$m,self::TTL);$index=$this->cache->get(self::INDEX,[]);$index[$toolName]=$key;$this->cache->put(self::INDEX,$index,self::TTL);if($latencyMs>self::SLOW_MS)Log::warning('Slow AI tool execution',['tool'=>$toolName,'latency_ms'=>$latencyMs]); }
    public function trackFromResult(string $toolName,AiToolResult $result): void { $this->recordExecution($toolName,$result->latencyMs,!isset($result->data['error'])); }
    public function getMetrics(string $toolName): array { return $this->cache->get(self::PREFIX.hash('sha256',$toolName),$this->defaults($toolName)); }
    public function getAllMetrics(): array { $out=[];foreach($this->cache->get(self::INDEX,[]) as $name=>$key)$out[$name]=$this->cache->get($key,$this->defaults($name));return $out; }
    public function clearMetrics(string $toolName): void { $key=self::PREFIX.hash('sha256',$toolName);$this->cache->forget($key);$index=$this->cache->get(self::INDEX,[]);unset($index[$toolName]);$this->cache->put(self::INDEX,$index,self::TTL); }
    private function defaults(string $name): array { return ['tool'=>$name,'executions'=>0,'successes'=>0,'failures'=>0,'slow_executions'=>0,'total_latency_ms'=>0,'avg_latency_ms'=>0.0,'last_execution_at'=>null]; }
}
