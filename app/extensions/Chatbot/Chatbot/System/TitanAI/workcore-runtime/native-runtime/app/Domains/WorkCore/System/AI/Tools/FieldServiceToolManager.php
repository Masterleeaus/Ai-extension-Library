<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\AI\Tools;

use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Domains\WorkCore\System\ReadModels\ReadModelRegistry;

/** Dependency-aware field-service tool catalogue. Definitions are exposed only when their authoritative target is installed. */
final class FieldServiceToolManager implements DomainToolRegistryContract
{
    public function __construct(private BusinessActionRegistry $actions, private ReadModelRegistry $reads) {}

    /** @return list<AiToolDefinition> */
    public function registerTools(): array
    {
        $definitions = [
            $this->read('cleaning.job_estimate','Generate a cleaning estimate with configurable pricing, time and resources.','cleaning.job_estimate','internal',['internal','worker_app','customer_portal']),
            $this->read('cleaning.supplies_recommendation','Recommend job-appropriate cleaning supplies and equipment.','cleaning.supplies_recommendation','internal',['internal','worker_app']),
            $this->read('cleaning.quality_checklist','Generate a cleaning quality inspection checklist.','cleaning.quality_checklist','internal',['internal','worker_app','customer_portal']),
            $this->read('cleaning.stain_removal_guide','Return safety-aware stain-removal guidance.','cleaning.stain_removal_guide','public',['internal','worker_app','customer_portal']),
            $this->read('handyman.quote_generator','Generate a configurable handyman quote breakdown.','handyman.quote_generator','internal',['internal','customer_portal']),
            $this->read('handyman.maintenance_schedule','Generate a property maintenance schedule.','handyman.maintenance_schedule','internal',['internal','customer_portal','owner_portal']),
            $this->read('handyman.parts_finder','Find compatible repair parts and supplies.','handyman.parts_finder','public',['internal','worker_app']),
            $this->action('field.dispatch_optimizer','Optimise technician dispatch and assignments.','field.dispatch_optimizer','internal',['internal','worker_app','dispatch']),
            $this->read('field.route_optimizer','Calculate efficient multi-job routes.','field.route_optimizer','internal',['internal','worker_app','dispatch']),
            $this->action('field.equipment_maintenance_tracker','Create or update equipment maintenance records.','field.equipment_maintenance_tracker','internal',['internal','worker_app']),
            $this->action('field.inventory_manager','Update, reorder, transfer or count field inventory.','field.inventory_manager','internal',['internal','worker_app']),
            $this->action('customer.service_notification','Send a governed customer service notification.','customer.service_notification','confidential',['internal','customer_portal']),
            $this->action('customer.satisfaction_survey','Send a post-service satisfaction survey.','customer.satisfaction_survey','confidential',['internal','customer_portal']),
            $this->action('customer.review_request','Send a governed review request.','customer.review_request','confidential',['internal','customer_portal']),
            $this->action('field.smart_scheduling','Create an AI-assisted schedule proposal or approved schedule.','field.smart_scheduling','confidential',['internal','dispatch']),
            $this->read('field.predictive_maintenance','Estimate maintenance risk from available equipment history.','field.predictive_maintenance','confidential',['internal','worker_app']),
            $this->read('field.quality_prediction','Estimate service-quality risk and contributing factors.','field.quality_prediction','confidential',['internal','dispatch']),
            $this->action('field.pricing_optimizer','Create an approval-gated pricing recommendation.','field.pricing_optimizer','confidential',['internal']),
            $this->read('field.lead_scoring','Score leads using configured criteria without protected attributes.','field.lead_scoring','confidential',['internal']),
        ];

        return array_values(array_filter($definitions, fn (AiToolDefinition $tool): bool =>
            $tool->kind === 'action' ? $this->actions->get($tool->target) !== null : $this->reads->get($tool->target) !== null
        ));
    }

    /** @param list<string> $channels */
    private function read(string $name,string $description,string $target,string $classification,array $channels): AiToolDefinition
    { return ToolDefinitionFactory::read($name,$description,$target,ToolDefinitionFactory::freeObject(),$classification,$channels); }

    /** @param list<string> $channels */
    private function action(string $name,string $description,string $target,string $classification,array $channels): AiToolDefinition
    { return ToolDefinitionFactory::action($name,$description,$target,ToolDefinitionFactory::freeObject(),$classification,$channels); }
}
