<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

final class IntentGateway
{
    /** @var array<string,array<string,mixed>> */
    private const ACTIONS = [
        'create_invoice' => ['patterns'=>['create invoice','create an invoice','make invoice','make an invoice','generate invoice','generate an invoice'],'manager'=>'finance-manager','assistant'=>'invoicing-assistant','agent'=>'create-invoice-agent','domain'=>'invoicing','operation'=>'create_invoice','risk'=>'amber','permissions'=>['jobs.read','invoices.create']],
        'send_invoice' => ['patterns'=>['send invoice','email invoice'],'manager'=>'finance-manager','assistant'=>'invoicing-assistant','agent'=>'send-invoice-agent','domain'=>'invoicing','operation'=>'send_invoice','risk'=>'red','permissions'=>['invoices.read','invoices.send']],
        'record_payment' => ['patterns'=>['record payment','mark paid','cash payment'],'manager'=>'finance-manager','assistant'=>'payments-assistant','agent'=>'record-payment-agent','domain'=>'invoicing','operation'=>'apply_payment','risk'=>'critical','permissions'=>['invoices.read','payments.create']],
        'create_quote' => ['patterns'=>['create quote','make quote','prepare quote'],'manager'=>'sales-manager','assistant'=>'quoting-assistant','agent'=>'create-quote-agent','domain'=>'invoicing','operation'=>'create_quote','risk'=>'amber','permissions'=>['customers.read','quotes.create']],
        'book_job' => ['patterns'=>['book job','book a job','create booking','create a booking','schedule cleaning','schedule a cleaning','book cleaning','book a cleaning'],'manager'=>'operations-manager','assistant'=>'scheduling-assistant','agent'=>'book-job-agent','domain'=>'jobs','operation'=>'create_job_order','risk'=>'amber','permissions'=>['customers.read','properties.read','jobs.create']],
        'reschedule_job' => ['patterns'=>['reschedule job','move booking','change appointment'],'manager'=>'operations-manager','assistant'=>'scheduling-assistant','agent'=>'reschedule-job-agent','domain'=>'scheduling','operation'=>'reschedule_appointment','risk'=>'amber','permissions'=>['jobs.read','schedules.update']],
        'cancel_job' => ['patterns'=>['cancel job','cancel booking','cancel appointment'],'manager'=>'operations-manager','assistant'=>'scheduling-assistant','agent'=>'cancel-job-agent','domain'=>'jobs','operation'=>'cancel_job','risk'=>'red','permissions'=>['jobs.read','jobs.cancel']],
        'assign_cleaner' => ['patterns'=>['assign cleaner','assign a cleaner','assign worker','assign a worker','dispatch cleaner','dispatch a cleaner'],'manager'=>'field-staff-manager','assistant'=>'dispatch-assistant','agent'=>'assign-cleaner-agent','domain'=>'jobs','operation'=>'assign_to_technician','risk'=>'amber','permissions'=>['jobs.read','workers.read','jobs.assign']],
        'optimise_route' => ['patterns'=>['optimise route','optimize route','plan route'],'manager'=>'operations-manager','assistant'=>'route-planning-assistant','agent'=>'optimise-route-agent','domain'=>'field_ops','operation'=>'optimize_route','risk'=>'green','permissions'=>['jobs.read','routes.update']],
        'complete_job' => ['patterns'=>['complete job','mark job complete','finish job'],'manager'=>'operations-manager','assistant'=>'quality-inspection-assistant','agent'=>'mark-job-complete-agent','domain'=>'jobs','operation'=>'complete_job','risk'=>'amber','permissions'=>['jobs.read','jobs.complete']],
        'create_customer' => ['patterns'=>['create customer','add customer','new customer'],'manager'=>'customer-manager','assistant'=>'customer-service-assistant','agent'=>'create-customer-agent','domain'=>'crm','operation'=>'create_contact','risk'=>'green','permissions'=>['customers.create']],
        'update_customer' => ['patterns'=>['update customer','edit customer'],'manager'=>'customer-manager','assistant'=>'customer-service-assistant','agent'=>'update-customer-agent','domain'=>'crm','operation'=>'update_contact','risk'=>'green','permissions'=>['customers.read','customers.update']],
        'update_stock' => ['patterns'=>['update stock','adjust stock','change inventory'],'manager'=>'supplies-equipment-manager','assistant'=>'inventory-assistant','agent'=>'update-stock-agent','domain'=>'inventory','operation'=>'adjust_stock','risk'=>'amber','permissions'=>['inventory.read','inventory.update']],
        'record_incident' => ['patterns'=>['record incident','report incident','safety incident'],'manager'=>'quality-safety-manager','assistant'=>'safety-compliance-assistant','agent'=>'record-incident-agent','domain'=>'field_ops','operation'=>'record_incident','risk'=>'red','permissions'=>['jobs.read','incidents.create']],
    ];

    /** @return array<string,array<string,mixed>> */
    public function actionDefinitions(): array
    {
        return self::ACTIONS;
    }

    public function classify(string $message): IntentDecision
    {
        $normalised = strtolower(trim($message));
        foreach (self::ACTIONS as $intent => $definition) {
            foreach ($definition['patterns'] as $pattern) {
                if (str_contains($normalised, $pattern)) {
                    return new IntentDecision('business_action', $intent, 0.95, $definition['manager'], $definition['assistant'], $definition['agent'], $definition['domain'], $definition['operation'], $definition['risk'], $definition['permissions']);
                }
            }
        }

        if (preg_match('/\b(how|what|why|where|when|help|procedure|policy|instructions?)\b/i', $message) === 1) {
            return new IntentDecision('knowledge', 'knowledge_query', 0.75);
        }

        return new IntentDecision('conversation', 'general_conversation', 0.65);
    }
}
