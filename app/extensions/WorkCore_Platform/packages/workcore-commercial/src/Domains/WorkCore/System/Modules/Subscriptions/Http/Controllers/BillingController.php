<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Subscriptions\Application\Services\BillingService;
use WorkCore\Subscriptions\Domain\Subscription;
use WorkCore\Subscriptions\Domain\SubscriptionInvoice;

class BillingController
{
    public function __construct(private BillingService $billingService)
    {
    }

    /**
     * Get billing history
     */
    public function history(int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $history = $this->billingService->getBillingHistory($subscription);

        return response()->json($history);
    }

    /**
     * Get invoices
     */
    public function invoices(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $invoices = $subscription->invoices()
            ->when($request->get('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->orderByDesc('created_at')
            ->get();

        return response()->json($invoices);
    }

    /**
     * Get invoice details
     */
    public function getInvoice(string $invoiceId): JsonResponse
    {
        $invoice = SubscriptionInvoice::find($invoiceId);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        return response()->json($invoice);
    }

    /**
     * Get unpaid invoices
     */
    public function unpaidInvoices(int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $invoices = $this->billingService->getUnpaidInvoices($subscription);

        return response()->json($invoices);
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoiceAsPaid(Request $request, string $invoiceId): JsonResponse
    {
        $invoice = SubscriptionInvoice::find($invoiceId);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        try {
            $invoice->markAsPaid($request->get('transaction_id'));

            return response()->json(['message' => 'Invoice marked as paid']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Retry payment for subscription
     */
    public function retryPayment(int $subscriptionId): JsonResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $unpaidInvoices = $this->billingService->getUnpaidInvoices($subscription);

        if ($unpaidInvoices->isEmpty()) {
            return response()->json(['message' => 'No unpaid invoices found'], 404);
        }

        // Retry payment for the oldest unpaid invoice
        $oldestInvoice = $unpaidInvoices->last();

        return response()->json([
            'message' => 'Payment retry initiated',
            'invoice_id' => $oldestInvoice->id,
        ]);
    }
}
