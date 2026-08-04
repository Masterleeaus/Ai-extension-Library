@extends('panel.layout.app')
@section('title', 'AI action approvals')
@section('content')
<div class="container-xl py-6">
    <h1 class="mb-4">AI action approvals</h1>
    <div class="grid gap-4">
        @forelse($approvals as $approval)
            <article class="rounded-xl border p-4" data-generative-ui="approval-card">
                <div class="flex items-center justify-between gap-4">
                    <div><strong>{{ $approval['action'] }}</strong><div class="text-sm opacity-70">Risk: {{ $approval['risk_level'] }}</div></div>
                    <time>{{ $approval['created_at'] }}</time>
                </div>
                <pre class="mt-3 overflow-auto rounded-lg bg-black/5 p-3 text-xs">{{ json_encode(json_decode($approval['payload'], true), JSON_PRETTY_PRINT) }}</pre>
                <div class="mt-3 flex gap-2">
                    <button type="button" data-approval-action="approve" data-approval-id="{{ $approval['id'] }}">Approve</button>
                    <button type="button" data-approval-action="reject" data-approval-id="{{ $approval['id'] }}">Reject</button>
                </div>
            </article>
        @empty
            <p>No pending approvals.</p>
        @endforelse
    </div>
</div>
@endsection
