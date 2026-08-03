<?php

namespace App\Extensions\CustomerTags\System\Http\Controllers;

use App\Extensions\CustomerTags\System\Http\Requests\CustomerTagRequest;
use App\Extensions\CustomerTags\System\Models\CustomerTags;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class CustomerTagController extends Controller
{
    public function index()
    {
        return view('customer-tags::tags.index', [
            'items'       => CustomerTags::query()->latest()->paginate(20),
            'title'       => __('customer-tags::messages.title'),
            'description' => __('customer-tags::messages.description'),
        ]);
    }

    public function store(CustomerTagRequest $request): JsonResponse|RedirectResponse
    {
        $tag = CustomerTags::query()->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => __('customer-tags::messages.created'),
                'tag'     => array_merge($tag->toArray(), ['created_at_formatted' => $tag->created_at?->format('Y-m-d H:i')]),
            ], 201);
        }

        return redirect()->route('dashboard.customer-tagss.index')
            ->with([
                'type'    => 'success',
                'message' => __('customer-tags::messages.created'),
            ]);
    }

    public function edit(CustomerTags $chatbotCustomerTag): JsonResponse|View
    {
        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'tag'    => $chatbotCustomerTag,
            ]);
        }

        return view('customer-tags::tags.edit', [
            'item'        => $chatbotCustomerTag,
            'action'      => route('dashboard.customer-tagss.update', $chatbotCustomerTag),
            'method'      => 'PUT',
            'title'       => __('customer-tags::messages.edit_title'),
            'description' => __('customer-tags::messages.edit_description'),
        ]);
    }

    public function update(CustomerTagRequest $request, CustomerTags $chatbotCustomerTag): JsonResponse|RedirectResponse
    {
        $chatbotCustomerTag->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => __('customer-tags::messages.updated'),
                'tag'     => array_merge($chatbotCustomerTag->toArray(), ['created_at_formatted' => $chatbotCustomerTag->created_at?->format('Y-m-d H:i')]),
            ]);
        }

        return redirect()->route('dashboard.customer-tagss.index')
            ->with([
                'type'    => 'success',
                'message' => __('customer-tags::messages.updated'),
            ]);
    }

    public function destroy(CustomerTags $chatbotCustomerTag): JsonResponse|RedirectResponse
    {
        $chatbotCustomerTag->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => __('customer-tags::messages.deleted'),
            ]);
        }

        return redirect()->route('dashboard.customer-tagss.index')
            ->with([
                'type'    => 'success',
                'message' => __('customer-tags::messages.deleted'),
            ]);
    }
}
