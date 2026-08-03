<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime\Tools;

use App\Services\AI\Tier4\Tools\AudioTranscriptionTool;
use App\Services\AI\Tier4\Tools\CalendarAvailabilityTool;
use App\Services\AI\Tier4\Tools\CalendarBookingTool;
use App\Services\AI\Tier4\Tools\DistanceMatrixTool;
use App\Services\AI\Tier4\Tools\DocumentSignatureTool;
use App\Services\AI\Tier4\Tools\EmailDeliveryTool;
use App\Services\AI\Tier4\Tools\FileStorageTool;
use App\Services\AI\Tier4\Tools\GeocodingTool;
use App\Services\AI\Tier4\Tools\InternalChatDeliveryTool;
use App\Services\AI\Tier4\Tools\NotificationTool;
use App\Services\AI\Tier4\Tools\OcrTool;
use App\Services\AI\Tier4\Tools\PaymentLinkTool;
use App\Services\AI\Tier4\Tools\PdfGenerationTool;
use App\Services\AI\Tier4\Tools\PushNotificationTool;
use App\Services\AI\Tier4\Tools\RouteOptimisationTool;
use App\Services\AI\Tier4\Tools\SmsDeliveryTool;
use App\Services\AI\Tier4\Tools\SpreadsheetTool;
use App\Services\AI\Tier4\Tools\VisionAnalysisTool;
use App\Services\AI\Tier4\Tools\VisualInspectionTool;
use App\Services\AI\Tier4\Tools\VoiceSynthesisTool;
use App\Services\AI\Tier4\Tools\WeatherTool;
use App\Services\AI\Tier4\Tools\WebhookDeliveryTool;
use App\Services\AI\Tier4\Tools\WhatsAppDeliveryTool;
use App\Services\AI\Tier4\Tools\WorkCoreActionTool;
use App\Services\AI\Tier4\Tools\WorkCoreReadTool;

final class FieldServiceToolRegistry
{
    /** @return list<class-string> */
    public function classes(): array
    {
        return [
            AudioTranscriptionTool::class,
            CalendarAvailabilityTool::class,
            CalendarBookingTool::class,
            DistanceMatrixTool::class,
            DocumentSignatureTool::class,
            EmailDeliveryTool::class,
            FileStorageTool::class,
            GeocodingTool::class,
            InternalChatDeliveryTool::class,
            NotificationTool::class,
            OcrTool::class,
            PaymentLinkTool::class,
            PdfGenerationTool::class,
            PushNotificationTool::class,
            RouteOptimisationTool::class,
            SmsDeliveryTool::class,
            SpreadsheetTool::class,
            VisionAnalysisTool::class,
            VisualInspectionTool::class,
            VoiceSynthesisTool::class,
            WeatherTool::class,
            WebhookDeliveryTool::class,
            WhatsAppDeliveryTool::class,
            WorkCoreActionTool::class,
            WorkCoreReadTool::class
        ];
    }

    /** @return array<string,array<string,mixed>> */
    public function all(): array
    {
        $definitions = [];
        foreach ($this->classes() as $class) {
            if (! class_exists($class) || ! method_exists($class, 'definition')) continue;
            $definition = $class::definition();
            $definitions[(string) $definition['id']] = $definition;
        }
        ksort($definitions);
        return $definitions;
    }

    /** @return list<array<string,mixed>> */
    public function match(string $message): array
    {
        $terms = array_values(array_filter(preg_split('/[^a-z0-9]+/i', strtolower($message)) ?: []));
        $scored = [];
        foreach ($this->all() as $definition) {
            $haystack = strtolower(implode(' ', [
                (string) ($definition['id'] ?? ''),
                (string) ($definition['name'] ?? ''),
                (string) ($definition['operation'] ?? ''),
                implode(' ', (array) ($definition['capabilities'] ?? [])),
            ]));
            $score = 0;
            foreach ($terms as $term) { if (strlen($term) >= 3 && str_contains($haystack, $term)) $score++; }
            if ($score > 0) $scored[] = $definition + ['match_score' => $score];
        }
        usort($scored, static fn(array $a,array $b): int => ($b['match_score'] <=> $a['match_score']) ?: strcmp((string)$a['id'], (string)$b['id']));
        return array_slice($scored, 0, 8);
    }
}
