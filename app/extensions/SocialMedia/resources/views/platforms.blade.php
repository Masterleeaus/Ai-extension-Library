@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Titan Reach Channels'))
@section('titlebar_actions', '')
@section('titlebar_subtitle', __('Connect and manage the destinations where your business publishes.'))

@section('content')
    <div class="py-10">
        <x-card class="mb-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <p class="text-xs font-medium uppercase opacity-60">@lang('Included channels')</p>
                    <p class="text-2xl font-semibold">{{ $channelUsage['included'] }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase opacity-60">@lang('Connected')</p>
                    <p class="text-2xl font-semibold">{{ $channelUsage['connected'] }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase opacity-60">@lang('Publishing active')</p>
                    <p class="text-2xl font-semibold">{{ $channelUsage['active'] }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase opacity-60">@lang('Projected monthly add-on')</p>
                    <p class="text-2xl font-semibold">{{ number_format($channelUsage['projected_monthly_add_on'], 2) }}</p>
                </div>
            </div>

            @if ($channelUsage['paused'] > 0)
                <p class="mt-4 text-sm text-orange-500">
                    {{ trans_choice(':count channel is paused because it is outside your current allowance.|:count channels are paused because they are outside your current allowance.', $channelUsage['paused'], ['count' => $channelUsage['paused']]) }}
                </p>
            @endif
        </x-card>

        @include('social-media::platforms.platform-statistics', ['items' => $userPlatforms])
        @include('social-media::platforms.platform-cards', ['platforms' => $platforms])
        @include('social-media::platforms.platform-table', ['items' => $userPlatforms])
    </div>
@endsection
