<?php

declare(strict_types=1);

namespace App\Extensions\AIPhotoshoot\System\Http\Controllers\Concerns;

use App\Domains\Entity\Enums\EntityEnum;
use App\Extensions\AIPhotoshoot\System\Enums\ImageStatusEnum;
use App\Extensions\AIPhotoshoot\System\Jobs\CheckBackgroundGenerationJob;
use App\Extensions\AIPhotoshoot\System\Jobs\GenerateOpenAIImageJob;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootFalService;
use App\Extensions\AIPhotoshoot\System\Services\AIPhotoshootImageModelRegistry;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Shared logic for AI Photoshoot asset-creator flows (currently
 * BackgroundController::create — the only AIPhotoshoot asset-creator that
 * generates from text). Resolves the admin-configured image model and
 * routes generation to either FAL (async, polled by
 * CheckBackgroundGenerationJob) or OpenAI (async, GenerateOpenAIImageJob).
 */
trait DispatchesAssetCreation
{
    /**
     * Asset creators always start from text (no reference images).
     */
    protected function resolveAssetCreationModel(): EntityEnum
    {
        return AIPhotoshootImageModelRegistry::getDefaultModel();
    }

    /**
     * Run generation against the resolved provider and update the asset record.
     *
     * @param  Model  $record  Asset model (currently AIPhotoshootBackground)
     * @param  string  $modelType  GenerateOpenAIImageJob model type key (e.g. 'background')
     */
    protected function dispatchAssetGeneration(
        Model $record,
        string $prompt,
        string $modelType,
        EntityEnum $model
    ): void {
        try {
            $provider = AIPhotoshootImageModelRegistry::getProviderFor($model);

            if ($provider === AIPhotoshootImageModelRegistry::PROVIDER_OPENAI) {
                $this->runOpenAIAssetGeneration($record, $prompt, $modelType, $model);

                return;
            }

            $this->runFalAssetGeneration($record, $prompt, $modelType, $model);
        } catch (Exception $e) {
            Log::error('AI Photoshoot asset generation failed', [
                'record_id'  => $record->id,
                'model_type' => $modelType,
                'error'      => $e->getMessage(),
            ]);

            $record->update(['status' => ImageStatusEnum::failed->value]);

            throw $e;
        }
    }

    /**
     * Async FAL flow: enqueue request, set processing, dispatch polling job.
     */
    protected function runFalAssetGeneration(
        Model $record,
        string $prompt,
        string $modelType,
        EntityEnum $model
    ): void {
        $requestId = AIPhotoshootFalService::generateFromText($prompt);

        $record->update([
            'status'          => ImageStatusEnum::processing->value,
            'generation_uuid' => $requestId,
        ]);

        if ($modelType === 'background') {
            CheckBackgroundGenerationJob::dispatch($record->id)->delay(now()->addSeconds(5));
        }
    }

    /**
     * Async OpenAI flow: enqueue the generation job and return immediately.
     */
    protected function runOpenAIAssetGeneration(
        Model $record,
        string $prompt,
        string $modelType,
        EntityEnum $model
    ): void {
        $record->update(['status' => ImageStatusEnum::processing->value]);

        GenerateOpenAIImageJob::dispatch(
            $record->id,
            $modelType,
            $model->value,
            $prompt,
            [],
            1
        )->delay(now()->addSeconds(2));
    }
}
