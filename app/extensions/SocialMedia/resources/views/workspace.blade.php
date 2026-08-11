@php
    use App\Extensions\SocialMedia\System\Models\DistributionItem;

    $sectionTitles = [
        'create' => __('Create'),
        'distribute' => __('Distribute'),
        'listings' => __('Listings'),
        'paid-media' => __('Paid Media'),
        'creative-studio' => __('Creative Studio'),
        'catalogues' => __('Catalogues'),
        'inbox' => __('Inbox'),
        'analytics' => __('Analytics'),
        'settings' => __('Settings'),
    ];

    $sectionDescriptions = [
        'create' => __('Create content that fits your business profile, then review destinations before anything is published.'),
        'distribute' => __('See where each content type can go and how Titan Reach can deliver it.'),
        'listings' => __('Manage marketplace, classified, property, vehicle, job, stay and hire listing drafts.'),
        'paid-media' => __('Prepare governed paid creative without activating spend or bypassing account approval.'),
        'creative-studio' => __('Use existing Titan creative tools with business-specific media guidance and calls to action.'),
        'catalogues' => __('Review the content catalogue, required facts and handoff rules for this business profile.'),
        'inbox' => __('Open governed provider engagement capabilities and inboxes for connected accounts.'),
        'analytics' => __('Review Titan Reach distribution activity for this tenant and resolved business profile.'),
        'settings' => __('Review safe customer workspace context and channel connections. Provider credentials remain administrator-only.'),
    ];

    $modeLegend = [
        'direct' => __('Direct'),
        'partner' => __('Partner / Feed'),
        'assisted' => __('Assisted'),
        'export_only' => __('Export only'),
    ];

    $contentTypeLabel = static fn (string $type): string => str($type)->replace('_', ' ')->title()->toString();
    $resolvedSubtype = $profile['resolved_subtype'] ?? null;
    $profileVersion = $profile['profile_version'] ?? null;
    $profileProvenance = (array) ($profile['profile_provenance'] ?? []);
    $complianceWarnings = (array) ($profile['compliance_warnings'] ?? []);
    $handoffTargets = (array) ($profile['handoff_targets'] ?? []);
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Titan Reach') . ' — ' . ($sectionTitles[$section] ?? __('Workspace')))
@section('subtitle', $sectionDescriptions[$section] ?? __('Create once. Reach everywhere.'))

@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.social-media.platforms') }}"
        variant="ghost-shadow"
    >
        @lang('Connect Channels')
    </x-button>

    <x-button href="{{ route('dashboard.user.social-media.post.create') }}">
        @lang('Create Social Post')
    </x-button>
@endsection

@section('content')
    <div class="py-10">
        @include('social-media::components.reach-navigation')

        <div class="space-y-8">
            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div class="max-w-2xl">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-primary/10 px-3 py-1 text-2xs font-semibold text-primary">
                                {{ $profile['label'] ?? __('Generic Business') }}
                            </span>
                            @if ($resolvedSubtype)
                                <span class="rounded-full bg-foreground/5 px-3 py-1 text-2xs font-medium">
                                    {{ str($resolvedSubtype)->replace('-', ' ')->title() }}
                                </span>
                            @endif
                            @if ($genericFallback)
                                <span class="rounded-full bg-amber-500/10 px-3 py-1 text-2xs font-medium text-amber-700 dark:text-amber-300">
                                    @lang('Generic fallback')
                                </span>
                            @endif
                        </div>

                        <h2 class="mb-2">{{ $sectionTitles[$section] ?? __('Workspace') }}</h2>
                        <p class="m-0 text-sm text-heading-foreground/60">
                            {{ $sectionDescriptions[$section] ?? '' }}
                        </p>
                    </div>

                    <div class="text-end text-2xs text-heading-foreground/50">
                        <div><strong>@lang('profile_version'):</strong> {{ $profileVersion ?: __('unversioned') }}</div>
                        <div><strong>@lang('resolved_subtype'):</strong> {{ $resolvedSubtype ?: __('none') }}</div>
                        <div><strong>@lang('profile_provenance'):</strong> {{ implode(', ', $profileProvenance) ?: __('generic-business') }}</div>
                    </div>
                </div>

                <form
                    class="mt-6 grid grid-cols-1 gap-4 border-t pt-5 md:grid-cols-3"
                    method="GET"
                    action="{{ route('dashboard.user.social-media.workspace', ['section' => $section]) }}"
                >
                    <x-forms.input
                        type="select"
                        name="vertical"
                        label="{{ __('Business profile') }}"
                    >
                        <option value="">{{ __('Auto / Generic Business') }}</option>
                        @foreach ($verticals as $vertical)
                            <option
                                value="{{ $vertical['slug'] }}"
                                @selected(($profile['slug'] ?? null) === $vertical['slug'])
                            >
                                {{ $vertical['label'] }}
                            </option>
                        @endforeach
                    </x-forms.input>

                    <x-forms.input
                        type="text"
                        name="subtype"
                        label="{{ __('Business subtype') }}"
                        value="{{ request('subtype', $resolvedSubtype) }}"
                        placeholder="{{ __('Optional subtype') }}"
                    />

                    <div class="flex items-end gap-2">
                        <x-button class="w-full" type="submit">
                            @lang('Apply Profile')
                        </x-button>
                    </div>
                </form>
            </x-card>

            @if (in_array($section, ['create', 'distribute', 'listings', 'paid-media', 'catalogues'], true))
                <x-card>
                    <form
                        class="flex flex-wrap items-end gap-3"
                        method="GET"
                        action="{{ route('dashboard.user.social-media.workspace', ['section' => $section]) }}"
                    >
                        <input type="hidden" name="vertical" value="{{ $profile['slug'] ?? '' }}">
                        <input type="hidden" name="subtype" value="{{ $resolvedSubtype }}">
                        <div class="min-w-60 grow">
                            <x-forms.input
                                type="select"
                                name="content_type"
                                label="{{ __('Content type') }}"
                            >
                                @foreach ($contentTypes as $contentType)
                                    <option value="{{ $contentType }}" @selected($selectedContentType === $contentType)>
                                        {{ $contentTypeLabel($contentType) }}
                                    </option>
                                @endforeach
                            </x-forms.input>
                        </div>
                        <x-button type="submit" variant="outline">@lang('Refresh suitability')</x-button>
                    </form>
                </x-card>
            @endif

            @if ($section === 'create')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($contentTypes as $contentType)
                        <x-card class="h-full">
                            <div class="flex h-full flex-col gap-4">
                                <div>
                                    <span class="text-2xs font-semibold uppercase tracking-wider text-heading-foreground/40">
                                        {{ $contentType }}
                                    </span>
                                    <h3 class="mt-2">{{ $contentTypeLabel($contentType) }}</h3>
                                </div>

                                @if ($contentType === DistributionItem::TYPE_SOCIAL_POST)
                                    <p class="grow text-sm text-heading-foreground/60">
                                        @lang('Social posts stay on the existing Titan Reach composer so scheduling, media and channel publishing remain compatible.')
                                    </p>
                                    <x-button href="{{ route('dashboard.user.social-media.post.create') }}">
                                        @lang('Open Social Composer')
                                    </x-button>
                                @else
                                    <form
                                        class="flex grow flex-col gap-4"
                                        method="POST"
                                        action="{{ route('dashboard.user.social-media.workspace.draft.store') }}"
                                    >
                                        @csrf
                                        <input type="hidden" name="content_type" value="{{ $contentType }}">
                                        <input type="hidden" name="vertical" value="{{ $profile['slug'] ?? '' }}">
                                        <input type="hidden" name="subtype" value="{{ $resolvedSubtype }}">

                                        <x-forms.input
                                            type="text"
                                            name="title"
                                            label="{{ __('Title') }}"
                                            value="{{ old('title') }}"
                                        />
                                        <x-forms.input
                                            type="textarea"
                                            name="content"
                                            label="{{ __('Content / description') }}"
                                        >{{ old('content') }}</x-forms.input>

                                        @foreach ((array) ($profile['required_fields'] ?? []) as $field)
                                            @continue(in_array($field, ['title', 'content', 'description'], true))
                                            <x-forms.input
                                                type="text"
                                                name="payload[{{ $field }}]"
                                                label="{{ str($field)->replace('_', ' ')->title() }}"
                                                value="{{ old('payload.' . $field) }}"
                                                required
                                            />
                                        @endforeach

                                        <x-button class="mt-auto" type="submit">
                                            @lang('Save governed draft')
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @elseif ($section === 'distribute')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($destinations as $destination)
                        @php
                            $isUnavailable = !$destination['available'] || $destination['suitability'] === 'not-applicable';
                        @endphp
                        <x-card class="h-full">
                            <div class="flex h-full flex-col gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="m-0">{{ $destination['label'] }}</h3>
                                        <p class="mt-1 text-2xs text-heading-foreground/50">{{ $destination['slug'] }}</p>
                                    </div>
                                    <span class="rounded-full bg-foreground/5 px-2.5 py-1 text-2xs font-semibold">
                                        {{ $modeLegend[$destination['mode']] ?? $destination['mode'] }}
                                    </span>
                                </div>
                                <div class="text-sm">
                                    <div><strong>@lang('Mode'):</strong> {{ $destination['mode'] }}</div>
                                    <div><strong>@lang('Suitability'):</strong> {{ $destination['suitability'] }}</div>
                                    <div><strong>@lang('Approval'):</strong> {{ $destination['approval_required'] ? __('Required') : __('Not required') }}</div>
                                </div>
                                <p class="grow text-xs text-heading-foreground/60">
                                    {{ $destination['reason'] ? str($destination['reason'])->replace('_', ' ')->title() : __('Ready for governed review.') }}
                                </p>
                                <button
                                    class="lqd-btn inline-flex items-center justify-center rounded-xl border px-4 py-2 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                                    type="button"
                                    @disabled($isUnavailable)
                                    aria-disabled="{{ $isUnavailable ? 'true' : 'false' }}"
                                >
                                    {{ $isUnavailable ? __('Unavailable for this profile') : __('Review destination') }}
                                </button>
                            </div>
                        </x-card>
                    @endforeach
                </div>

                <x-card>
                    <h3 class="mb-4">@lang('Delivery modes')</h3>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        @foreach ($modeLegend as $mode => $label)
                            <div class="rounded-xl border p-4">
                                <div class="text-sm font-semibold">{{ $label }}</div>
                                <div class="mt-1 text-2xs text-heading-foreground/50">{{ $mode }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @elseif ($section === 'listings')
                <x-card>
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="m-0">@lang('Listing drafts')</h3>
                            <p class="mt-1 text-xs text-heading-foreground/50">@lang('Canonical source records stay in their owning Titan product.') </p>
                        </div>
                        <x-button href="{{ route('dashboard.user.social-media.workspace', ['section' => 'create', 'vertical' => $profile['slug'] ?? null]) }}">
                            @lang('Create Listing Draft')
                        </x-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-start text-sm">
                            <thead class="border-b text-2xs uppercase text-heading-foreground/50">
                                <tr>
                                    <th class="py-3 pe-4">@lang('Title')</th>
                                    <th class="py-3 pe-4">@lang('Type')</th>
                                    <th class="py-3 pe-4">@lang('Status')</th>
                                    <th class="py-3">@lang('Source')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr class="border-b last:border-0">
                                        <td class="py-4 pe-4 font-medium">{{ $item->title ?: __('Untitled draft') }}</td>
                                        <td class="py-4 pe-4">{{ $contentTypeLabel($item->content_type) }}</td>
                                        <td class="py-4 pe-4">{{ str($item->status)->title() }}</td>
                                        <td class="py-4">{{ $item->source_type ?: __('manual') }} {{ $item->source_id ? '#'.$item->source_id : '' }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="py-8 text-center text-heading-foreground/50" colspan="4">@lang('No listing drafts yet.')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @elseif ($section === 'paid-media')
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <x-card class="lg:col-span-2">
                        <h3 class="mb-3">@lang('Paid creative drafts')</h3>
                        <p class="mb-5 text-sm text-heading-foreground/60">
                            @lang('Titan Reach may prepare paid creative, but spend activation, budget changes and provider publication always require separate governed authority.')
                        </p>
                        <div class="space-y-3">
                            @forelse ($items as $item)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4">
                                    <div>
                                        <div class="font-semibold">{{ $item->title ?: __('Untitled paid creative') }}</div>
                                        <div class="text-2xs text-heading-foreground/50">{{ $item->approval_status }}</div>
                                    </div>
                                    <span class="rounded-full bg-amber-500/10 px-3 py-1 text-2xs font-semibold text-amber-700 dark:text-amber-300">
                                        @lang('No spend authority')
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-heading-foreground/50">@lang('No paid creative drafts yet.')</p>
                            @endforelse
                        </div>
                    </x-card>
                    <x-card>
                        <h3 class="mb-3">@lang('Meta Ads')</h3>
                        <p class="text-sm text-heading-foreground/60">
                            @lang('Partner-mode destination. Creative can be prepared for approval; Titan Reach does not automatically create spend authority.')
                        </p>
                        <div class="mt-4 text-2xs font-semibold">partner</div>
                    </x-card>
                </div>
            @elseif ($section === 'creative-studio')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <x-card>
                        <h3 class="mb-2">@lang('Social Composer')</h3>
                        <p class="mb-5 text-sm text-heading-foreground/60">@lang('Create channel-native social content using the existing composer.')</p>
                        <x-button href="{{ route('dashboard.user.social-media.post.create') }}">@lang('Open Composer')</x-button>
                    </x-card>
                    <x-card>
                        <h3 class="mb-2">@lang('Campaigns')</h3>
                        <p class="mb-5 text-sm text-heading-foreground/60">@lang('Reuse the existing campaign workflow rather than creating a second generator.') </p>
                        <x-button href="{{ route('dashboard.user.social-media.campaign.index') }}" variant="outline">@lang('Open Campaigns')</x-button>
                    </x-card>
                    <x-card>
                        <h3 class="mb-3">@lang('Recommended calls to action')</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ((array) ($profile['calls_to_action'] ?? []) as $callToAction)
                                <span class="rounded-full bg-foreground/5 px-3 py-1 text-2xs">{{ $callToAction }}</span>
                            @endforeach
                        </div>
                    </x-card>
                </div>

                <x-card>
                    <h3 class="mb-3">@lang('Media guidance')</h3>
                    <ul class="list-disc space-y-2 ps-5 text-sm text-heading-foreground/70">
                        @forelse ((array) ($profile['media_guidance'] ?? []) as $guidance)
                            <li>{{ $guidance }}</li>
                        @empty
                            <li>@lang('Use truthful, current brand and business imagery.')</li>
                        @endforelse
                    </ul>
                </x-card>
            @elseif ($section === 'catalogues')
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <x-card>
                        <h3 class="mb-4">@lang('Supported content catalogue')</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($contentTypes as $contentType)
                                <span class="rounded-full bg-foreground/5 px-3 py-1.5 text-xs">{{ $contentTypeLabel($contentType) }}</span>
                            @endforeach
                        </div>
                    </x-card>
                    <x-card>
                        <h3 class="mb-4">@lang('Required operational facts')</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ((array) ($profile['required_fields'] ?? []) as $field)
                                <span class="rounded-full border px-3 py-1.5 text-xs">{{ str($field)->replace('_', ' ')->title() }}</span>
                            @endforeach
                        </div>
                    </x-card>
                    <x-card>
                        <h3 class="mb-4">@lang('handoff_targets')</h3>
                        <div class="space-y-3 text-sm">
                            @foreach ($handoffTargets as $handoff => $targets)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border p-3">
                                    <span class="font-semibold">{{ str($handoff)->replace('_', ' ')->title() }}</span>
                                    <span class="text-heading-foreground/60">{{ implode(', ', (array) $targets) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                    <x-card>
                        <h3 class="mb-4">@lang('compliance_warnings')</h3>
                        <ul class="list-disc space-y-2 ps-5 text-sm text-heading-foreground/70">
                            @forelse ($complianceWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @empty
                                <li>@lang('Do not invent prices, availability, credentials or compliance claims.')</li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
            @elseif ($section === 'inbox')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($accounts as $account)
                        @php
                            $isTikTok = (string) $account->platform === 'tiktok';
                            $connected = $account->isConnected();
                        @endphp
                        <x-card class="h-full">
                            <div class="flex h-full flex-col gap-4">
                                <div>
                                    <h3 class="m-0">{{ str($account->platform)->replace('-', ' ')->title() }}</h3>
                                    <p class="mt-1 text-2xs text-heading-foreground/50">
                                        {{ data_get($account->credentials, 'name', data_get($account->credentials, 'username', __('Connected account'))) }}
                                    </p>
                                </div>
                                @if ($isTikTok)
                                    <p class="grow text-sm text-heading-foreground/60">
                                        @lang('Commercial TikTok comment management is unavailable through the normal public developer API. Titan Reach does not simulate it.')
                                    </p>
                                @else
                                    <p class="grow text-sm text-heading-foreground/60">
                                        @lang('Capabilities are resolved from the connected account permissions before any reply, edit or delete action is offered.')
                                    </p>
                                @endif
                                <div class="flex flex-wrap gap-2">
                                    <x-button
                                        href="{{ route('dashboard.user.social-media.engagement.capabilities', $account->id) }}"
                                        variant="outline"
                                        :disabled="!$connected"
                                    >
                                        @lang('Capabilities')
                                    </x-button>
                                    @unless ($isTikTok)
                                        <x-button
                                            href="{{ route('dashboard.user.social-media.engagement.inbox', $account->id) }}"
                                            :disabled="!$connected"
                                        >
                                            @lang('Open Inbox')
                                        </x-button>
                                    @endunless
                                </div>
                            </div>
                        </x-card>
                    @empty
                        <x-card class="md:col-span-2 xl:col-span-3">
                            <p class="m-0 text-sm text-heading-foreground/60">@lang('Connect a supported account to discover governed inbox capabilities.')</p>
                        </x-card>
                    @endforelse
                </div>
            @elseif ($section === 'analytics')
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach ([
                        'total_items' => __('Distribution items'),
                        'draft_items' => __('Drafts'),
                        'approved_items' => __('Approved'),
                        'listing_items' => __('Listings'),
                        'paid_creatives' => __('Paid creatives'),
                    ] as $metric => $label)
                        <x-card>
                            <div class="text-3xl font-semibold">{{ number_format((int) ($metrics[$metric] ?? 0)) }}</div>
                            <div class="mt-1 text-xs text-heading-foreground/50">{{ $label }}</div>
                        </x-card>
                    @endforeach
                </div>
                <x-card>
                    <h3 class="mb-3">{{ $profile['label'] ?? __('Generic Business') }} @lang('analytics context')</h3>
                    <p class="m-0 text-sm text-heading-foreground/60">
                        @lang('These counts are tenant-scoped Titan Reach distribution records. Provider-native performance remains in the existing channel analytics workflows.')
                    </p>
                </x-card>
            @elseif ($section === 'settings')
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <x-card>
                        <h3 class="mb-3">@lang('Business context')</h3>
                        <p class="text-sm text-heading-foreground/60">
                            @lang('Titan Reach resolves your canonical vertical and subtype from the shared Titan Vertical Context Engine when available. Customer overrides cannot create a new top-level vertical or expand a not-applicable destination.')
                        </p>
                        <div class="mt-4 space-y-2 text-sm">
                            <div><strong>@lang('Profile'):</strong> {{ $profile['label'] ?? __('Generic Business') }}</div>
                            <div><strong>@lang('Subtype'):</strong> {{ $resolvedSubtype ?: __('Not resolved') }}</div>
                            <div><strong>@lang('Version'):</strong> {{ $profileVersion ?: __('unversioned') }}</div>
                        </div>
                    </x-card>
                    <x-card>
                        <h3 class="mb-3">@lang('Channel connections')</h3>
                        <p class="mb-5 text-sm text-heading-foreground/60">
                            @lang('Connect or disconnect customer publishing accounts here. Provider application credentials and secrets remain administrator-only.')
                        </p>
                        <x-button href="{{ route('dashboard.user.social-media.platforms') }}">
                            @lang('Manage Connections')
                        </x-button>
                    </x-card>
                </div>
            @endif

            @if ($complianceWarnings !== [] && !in_array($section, ['catalogues', 'settings'], true))
                <x-card>
                    <h3 class="mb-3">@lang('Compliance guidance')</h3>
                    <ul class="list-disc space-y-2 ps-5 text-sm text-heading-foreground/70">
                        @foreach ($complianceWarnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
@endsection
