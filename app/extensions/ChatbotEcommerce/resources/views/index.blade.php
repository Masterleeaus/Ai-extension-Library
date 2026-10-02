@extends('panel.layout.app', ['disable_tblr' => true, 'disable_mobile_bottom_menu' => true])

@section('title', 'Titan Commerce')

@section('titlebar_subtitle')
    {{ __('One seller catalogue. Three coordinated AI roles.') }}
@endsection

@section('content')
    <div class="space-y-8 py-8">
        <section class="relative overflow-hidden rounded-2xl bg-[#101827] px-8 py-10 text-white shadow-xl">
            <div class="absolute -right-20 -top-28 h-80 w-80 rounded-full bg-cyan-400/15 blur-3xl"></div>
            <div class="relative max-w-3xl">
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-300">SELLER COMMERCE</p>
                <h1 class="text-3xl font-semibold tracking-tight md:text-4xl">Your product line, represented everywhere.</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
                    Run your store, connected marketplaces, hire and booking offers, and customer follow-up from one seller-owned commerce system.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <span class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm">Customer Shopping Assistant</span>
                    <span class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm">Seller Commerce Steward</span>
                    <span class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm">Customer Communications Agent</span>
                </div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-cyan-600">01 · ATTRACT</p>
                <h2 class="mt-3 text-lg font-semibold">Customer Shopping Assistant</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Answers from your catalogue, compares offers, finds booking times and guides customers through approved purchase steps.</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">02 · OPERATE</p>
                <h2 class="mt-3 text-lg font-semibold">Seller Commerce Steward</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Coordinates connected listings, inventory, orders, bookings and sales operations under seller authority.</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">03 · RETAIN</p>
                <h2 class="mt-3 text-lg font-semibold">Customer Communications Agent</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Follows up on sales, answers from transaction facts, requests feedback and escalates sensitive issues.</p>
            </article>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('Commerce command view') }}</h2>
                    <p id="commerce-store-caption" class="mt-1 text-sm text-slate-500">{{ __('Select a storefront to view its current commerce activity.') }}</p>
                </div>
                <div class="w-full sm:max-w-sm">
                    <label for="titan-commerce-chatbot" class="block text-sm font-medium">{{ __('Seller storefront') }}</label>
                    <select id="titan-commerce-chatbot" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">
                        <option value="">{{ __('Select a storefront') }}</option>
                        @foreach ($agentOptions as $agent)
                            <option value="{{ $agent->uuid }}">{{ $agent->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="commerce-summary-empty" class="mt-6 rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
                {{ __('Choose a storefront to load its seller-scoped commerce summary.') }}
            </div>
            <div id="commerce-summary" class="mt-6 hidden grid-cols-2 gap-3 md:grid-cols-4">
                <article class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Active catalogue items') }}</p><p data-commerce-count="products" class="mt-2 text-2xl font-semibold">0</p></article>
                <article class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Connected channels') }}</p><p data-commerce-count="channels" class="mt-2 text-2xl font-semibold">0</p></article>
                <article class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Open orders') }}</p><p data-commerce-count="open_orders" class="mt-2 text-2xl font-semibold">0</p></article>
                <article class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Available time slots') }}</p><p data-commerce-count="available_bookings" class="mt-2 text-2xl font-semibold">0</p></article>
                <article class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Active reservations') }}</p><p data-commerce-count="active_bookings" class="mt-2 text-2xl font-semibold">0</p></article>
                <article class="rounded-xl bg-amber-50 p-4 dark:bg-amber-950/30"><p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ __('Support needing attention') }}</p><p data-commerce-count="support_attention" class="mt-2 text-2xl font-semibold">0</p></article>
                <article class="rounded-xl bg-rose-50 p-4 dark:bg-rose-950/30"><p class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-300">{{ __('Order exceptions') }}</p><p data-commerce-count="exceptions" class="mt-2 text-2xl font-semibold">0</p></article>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-semibold">{{ __('One product line across commerce modes') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('The seller catalogue can include goods, services, hire and capacity-based offers.') }}</p>
                <ul class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Products, variants &amp; channel listings</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Services &amp; appointments</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Hire periods &amp; agreements</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Bookings &amp; capacity reservations</li>
                </ul>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-semibold">{{ __('Governed selling') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Listing changes, customer support actions and reservations follow role permissions, seller policies, idempotency and approval rules. Marketplace and payment providers require seller connection and configuration.</p>
                <p class="mt-4 text-xs leading-5 text-slate-500">{{ __('Counts reflect records stored for the selected storefront. Provider connection status and external sales depend on the configured integration.') }}</p>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const selector = document.getElementById('titan-commerce-chatbot');
            const summary = document.getElementById('commerce-summary');
            const empty = document.getElementById('commerce-summary-empty');
            const caption = document.getElementById('commerce-store-caption');
            const summaries = @json($dashboardSummaries);

            selector?.addEventListener('change', () => {
                const storeId = selector.value;
                const counts = summaries[storeId];
                if (!storeId || !counts) {
                    summary?.classList.add('hidden');
                    summary?.classList.remove('grid');
                    empty?.classList.remove('hidden');
                    caption.textContent = '{{ __('Select a storefront to view its current commerce activity.') }}';
                    return;
                }

                Object.entries(counts).forEach(([key, value]) => {
                    const target = summary.querySelector('[data-commerce-count="' + key + '"]');
                    if (target) target.textContent = new Intl.NumberFormat().format(Number(value || 0));
                });
                caption.textContent = selector.options[selector.selectedIndex].text;
                empty?.classList.add('hidden');
                summary?.classList.remove('hidden');
                summary?.classList.add('grid');
            });
        })();
    </script>
@endsection
