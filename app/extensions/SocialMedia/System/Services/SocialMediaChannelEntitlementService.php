<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Support\Collection;

class SocialMediaChannelEntitlementService
{
    public function usage(User $user): array
    {
        $channels = $this->connectedChannels($user);
        $included = $this->includedChannels($user);
        $purchased = $this->purchasedExtraChannels($user);
        $entitled = max(0, $included + $purchased);
        $active = min($channels->count(), $entitled);
        $paused = max(0, $channels->count() - $entitled);
        $unbilledExtra = max(0, $channels->count() - $included - $purchased);

        return [
            'included' => $included,
            'purchased_extra' => $purchased,
            'entitled' => $entitled,
            'connected' => $channels->count(),
            'active' => $active,
            'paused' => $paused,
            'extra_channel_rate' => $this->extraChannelRate(),
            'projected_monthly_add_on' => round($unbilledExtra * $this->extraChannelRate(), 2),
            'allowed_channel_ids' => $channels->take($entitled)->pluck('id')->all(),
        ];
    }

    public function canPublish(User $user, SocialMediaPlatform $platform): bool
    {
        if ((int) $platform->user_id !== (int) $user->getKey()) {
            return false;
        }

        return in_array($platform->getKey(), $this->usage($user)['allowed_channel_ids'], true);
    }

    public function status(User $user, SocialMediaPlatform $platform): string
    {
        if (! $platform->isConnected()) {
            return 'unavailable';
        }

        return $this->canPublish($user, $platform) ? 'billable-active' : 'paused';
    }

    private function connectedChannels(User $user): Collection
    {
        return SocialMediaPlatform::query()
            ->where('user_id', $user->getKey())
            ->connected()
            ->orderBy('connected_at')
            ->orderBy('id')
            ->get();
    }

    private function includedChannels(User $user): int
    {
        $plan = method_exists($user, 'activePlan') ? $user->activePlan() : null;
        $field = (string) config('social-media.channels.plan_included_field', 'titan_reach_included_channels');
        $value = $plan?->{$field};

        return max(0, (int) ($value ?? config('social-media.channels.included', 3)));
    }

    private function purchasedExtraChannels(User $user): int
    {
        $plan = method_exists($user, 'activePlan') ? $user->activePlan() : null;
        $field = (string) config('social-media.channels.plan_extra_field', 'titan_reach_extra_channels');

        return max(0, (int) ($plan?->{$field} ?? 0));
    }

    private function extraChannelRate(): float
    {
        return max(0, (float) config('social-media.channels.extra_monthly_rate', 10));
    }
}
