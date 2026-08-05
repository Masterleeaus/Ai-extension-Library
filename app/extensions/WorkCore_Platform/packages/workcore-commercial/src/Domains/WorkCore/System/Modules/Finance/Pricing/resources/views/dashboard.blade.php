<x-layouts.app>
    <section data-workcore-pricing-dashboard class="space-y-6">
        <header>
            <h1 class="text-2xl font-semibold">Dynamic Pricing</h1>
            <p class="text-sm text-slate-500">Demand, seasonality, occupancy and competitor-aware price decisions.</p>
        </header>
        <div class="grid gap-4 md:grid-cols-4" data-pricing-impact>
            <article class="rounded-xl border p-4"><span class="text-sm text-slate-500">Decisions</span><strong class="block text-2xl">{{ number_format($analytics['decision_count'] ?? 0) }}</strong></article>
            <article class="rounded-xl border p-4"><span class="text-sm text-slate-500">Revenue impact</span><strong class="block text-2xl">{{ number_format(($analytics['projected_revenue_impact_minor'] ?? 0) / 100, 2) }}</strong></article>
            <article class="rounded-xl border p-4"><span class="text-sm text-slate-500">Average change</span><strong class="block text-2xl">{{ number_format($analytics['average_price_change_percent'] ?? 0, 2) }}%</strong></article>
            <article class="rounded-xl border p-4"><span class="text-sm text-slate-500">Market index</span><strong class="block text-2xl">{{ number_format($analytics['market_price_index'] ?? 1, 2) }}</strong></article>
        </div>
        <article class="rounded-xl border p-4">
            <h2 class="font-semibold">Recommendation</h2>
            <p>{{ str_replace('_', ' ', $analytics['recommendation']['direction'] ?? 'hold') }}</p>
        </article>
        <div class="overflow-x-auto rounded-xl border">
            <table class="min-w-full text-sm">
                <thead><tr><th class="p-3 text-left">Target</th><th class="p-3 text-right">Base</th><th class="p-3 text-right">Final</th><th class="p-3 text-left">Calculated</th></tr></thead>
                <tbody>
                @forelse (($analytics['history'] ?? []) as $decision)
                    <tr class="border-t"><td class="p-3">{{ $decision['target_type'] }} / {{ $decision['target_reference'] }}</td><td class="p-3 text-right">{{ number_format($decision['base_price_minor'] / 100, 2) }}</td><td class="p-3 text-right">{{ number_format($decision['final_price_minor'] / 100, 2) }}</td><td class="p-3">{{ $decision['calculated_at'] }}</td></tr>
                @empty
                    <tr><td class="p-6 text-center text-slate-500" colspan="4">No applied price decisions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
