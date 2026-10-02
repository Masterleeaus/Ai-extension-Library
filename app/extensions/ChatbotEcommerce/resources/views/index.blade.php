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
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-300">Seller Commerce OS</p>
                <h1 class="text-3xl font-semibold tracking-tight md:text-4xl">Your products, represented everywhere.</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
                    Coordinate storefront shopping, marketplace operations and customer follow-up around one seller-owned catalogue, with hire and booking offers alongside products.
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
                <p class="text-xs font-semibold uppercase tracking-wider text-cyan-600">01 · Attract</p>
                <h2 class="mt-3 text-lg font-semibold">Customer Shopping Assistant</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Answers from your catalogue, compares your offers, finds available booking times and guides customers through approved purchase steps.</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">02 · Operate</p>
                <h2 class="mt-3 text-lg font-semibold">Seller Commerce Steward</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Coordinates connected listings, sales, inventory, bookings and order work, with seller authority and review controls.</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">03 · Retain</p>
                <h2 class="mt-3 text-lg font-semibold">Customer Communications Agent</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Follows up on enquiries and sales, shares factual order updates, requests feedback and escalates sensitive issues.</p>
            </article>
        </section>

        <section class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-semibold">{{ __('Choose a seller storefront') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Select the chatbot whose commerce configuration you want to manage.') }}</p>
                <label for="titan-commerce-chatbot" class="mt-5 block text-sm font-medium">{{ __('Storefront') }}</label>
                <select id="titan-commerce-chatbot" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <option value="">{{ __('Select a storefront') }}</option>
                    @foreach ($agentOptions as $agent)
                        <option value="{{ $agent->uuid ?? $agent->id }}">{{ $agent->title }}</option>
                    @endforeach
                </select>
                <p class="mt-3 text-xs leading-5 text-slate-500">{{ __('Commerce records, marketplace connections and booking capacity are scoped to the selected storefront.') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-semibold">{{ __('Commerce workspace') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Seller operations available through the Titan Commerce API.') }}</p>
                <ul class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Catalogue &amp; listings</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Marketplace connections</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Inventory &amp; availability</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Orders &amp; fulfilment</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Bookings &amp; reservations</li>
                    <li class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">Customer communications</li>
                </ul>
            </div>
        </section>

        <p class="text-xs leading-5 text-slate-500">
            {{ __('Marketplace providers, payments and booking policies require seller configuration. Actions that affect customers or channels follow the configured approval and authority rules.') }}
        </p>
    </div>
@endsection
