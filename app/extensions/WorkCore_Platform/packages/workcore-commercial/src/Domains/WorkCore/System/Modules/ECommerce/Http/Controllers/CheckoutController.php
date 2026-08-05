<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CheckoutService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Services\CartService;
use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources\OrderResource;

class CheckoutController
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected CartService $cartService
    ) {
    }

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer',
            'customer_email' => 'required|email',
            'shipping_address' => 'required|array',
            'shipping_address.street' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.country' => 'required|string',
            'billing_address' => 'required|array',
            'billing_address.street' => 'required|string',
            'billing_address.city' => 'required|string',
            'billing_address.state' => 'required|string',
            'billing_address.postal_code' => 'required|string',
            'billing_address.country' => 'required|string',
            'payment_method' => 'required|in:card,paypal,stripe',
            'payment_details' => 'required|array',
        ]);

        try {
            $cart = $this->cartService->getCart($validated['cart_id']);

            if (!$cart) {
                return response()->json(['error' => 'Cart not found'], 404);
            }

            // Validate cart
            $errors = $this->checkoutService->validateCart($cart);
            if (!empty($errors)) {
                return response()->json(['errors' => $errors], 400);
            }

            // Process payment
            $paymentResult = $this->checkoutService->processPayment(
                $cart,
                $validated['payment_method'],
                $validated['payment_details']
            );

            if (!$paymentResult['success']) {
                return response()->json(['error' => $paymentResult['error'] ?? 'Payment failed'], 400);
            }

            // Create order
            $order = $this->checkoutService->createOrder(
                $cart,
                $validated['customer_email'],
                $validated['shipping_address'],
                $validated['billing_address'],
                $validated['payment_method'],
                auth()->user()?->id
            );

            // Mark order as paid
            $order->markAsPaid();

            return response()->json(new OrderResource($order), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer',
        ]);

        $cart = $this->cartService->getCart($validated['cart_id']);

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $summary = $this->checkoutService->getCheckoutSummary($cart);

        return response()->json($summary);
    }
}
