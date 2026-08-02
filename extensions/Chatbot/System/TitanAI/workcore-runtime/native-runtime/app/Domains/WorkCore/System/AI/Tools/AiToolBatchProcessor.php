<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\AI\Tools;

use App\Domains\WorkCore\System\AI\DTO\AiToolExecutionContext;
use App\Domains\WorkCore\System\AI\DTO\AiToolResult;
use InvalidArgumentException;
use Throwable;

/** Governed sequential batch execution. Parallel execution is intentionally avoided because Laravel containers and DB contexts are not thread-safe. */
final class AiToolBatchProcessor
{
    public const MAX_BATCH_SIZE = 50;
    public function __construct(private AiToolRouter $router, private ToolPerformanceMonitor $monitor) {}

    /** @param list<array{name:string,input:array<string,mixed>}> $toolCalls @return list<AiToolResult> */
    public function batchExecute(array $toolCalls, AiToolExecutionContext $context, array $options = []): array
    {
        if (count($toolCalls) > self::MAX_BATCH_SIZE) throw new InvalidArgumentException('Batch exceeds maximum size of '.self::MAX_BATCH_SIZE.'.');
        $continueOnError=(bool)($options['continue_on_error']??true); $results=[];
        foreach($toolCalls as $index=>$call){
            if(!isset($call['name'],$call['input'])||!is_string($call['name'])||!is_array($call['input'])) throw new InvalidArgumentException("Invalid tool call at index {$index}.");
            $start=hrtime(true);
            try{$result=$this->router->execute($call['name'],$call['input'],$context);$this->monitor->trackFromResult($call['name'],$result);$results[]=$result;}
            catch(Throwable $e){$this->monitor->recordExecution($call['name'],(int)((hrtime(true)-$start)/1_000_000),false);if(!$continueOnError)throw $e;$results[]=new AiToolResult($call['name'],'error','read_model',['error'=>$e->getMessage()],0);}
        }
        return $results;
    }

    /** @param list<AiToolResult> $results */
    public function getBatchStatus(array $results): array
    { $failed=count(array_filter($results,fn(AiToolResult $r)=>isset($r->data['error'])));$total=count($results);return ['total'=>$total,'success'=>$total-$failed,'failed'=>$failed,'success_rate'=>$total?round((($total-$failed)/$total)*100,2):0.0]; }
}
