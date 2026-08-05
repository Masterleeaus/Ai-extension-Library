# Titan Reach channel entitlements

## Commercial defaults

- Base subscriptions include 3 active connected channels by default.
- Extra channel price defaults to 10 per month in the host billing currency.
- Both defaults can be changed without code by setting `TITAN_REACH_INCLUDED_CHANNELS` and `TITAN_REACH_EXTRA_CHANNEL_MONTHLY_RATE`.
- A plan may override the defaults through `titan_reach_included_channels` and `titan_reach_extra_channels`. The field names can be changed with `TITAN_REACH_PLAN_INCLUDED_FIELD` and `TITAN_REACH_PLAN_EXTRA_FIELD`.

## Seat projection

Connected, non-expired channels are ordered by connection time and ID. The first included-plus-purchased channels are `billable-active`. Any remaining connected channels are retained as `paused`; their accounts, posts, schedules and history are not deleted.

Changing the active plan or purchased extra-channel count recalculates the projection on the next request or publishing attempt. This provides downgrade-safe read-only behaviour without destructive account changes.

## Enforcement

All provider publication resolves through `PublisherDriver`, which refuses paused or cross-user channels before selecting a provider adapter. Manual publish requests receive a governed validation response. Scheduled publishing logs and skips paused channels without stopping other eligible posts.

## Native UI

The existing `resources/views/platforms.blade.php` page is edited in place. It displays included, connected, active, paused and projected monthly add-on usage while retaining the existing SocialMedia platform cards, statistics and table components.
