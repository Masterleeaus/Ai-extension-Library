<?php

declare(strict_types=1);

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\DistributionCapabilityService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ReachWorkspaceController extends Controller
{
    private const SECTIONS = [
        'create',
        'distribute',
        'listings',
        'paid-media',
        'creative-studio',
        'catalogues',
        'inbox',
        'analytics',
        'settings',
    ];

    private const LISTING_TYPES = [
        DistributionItem::TYPE_MARKETPLACE_LISTING,
        DistributionItem::TYPE_CLASSIFIED_LISTING,
        DistributionItem::TYPE_PROPERTY_LISTING,
        DistributionItem::TYPE_VEHICLE_LISTING,
        DistributionItem::TYPE_JOB_LISTING,
        DistributionItem::TYPE_ROOM_STAY_OFFER,
        DistributionItem::TYPE_HIRE_RENTAL_LISTING,
    ];

    public function __construct(private readonly DistributionCapabilityService $capabilities) {}

    public function show(Request $request, string $section): View
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $user = $this->user();
        $profile = $this->capabilities->resolveVerticalProfile(
            $request->string('vertical')->toString() ?: null,
            $request->string('subtype')->toString() ?: null,
            $user
        );
        $contentTypes = array_values((array) ($profile['content_types'] ?? []));
        $selectedContentType = $request->string('content_type')->toString();

        if ($selectedContentType === '' || ! in_array($selectedContentType, $contentTypes, true)) {
            $selectedContentType = $contentTypes[0] ?? DistributionItem::TYPE_SOCIAL_POST;
        }

        $itemsQuery = DistributionItem::query()
            ->where('user_id', Auth::id())
            ->latest('id');

        if ($section === 'listings') {
            $itemsQuery->whereIn('content_type', self::LISTING_TYPES);
        } elseif ($section === 'paid-media') {
            $itemsQuery->where('content_type', DistributionItem::TYPE_PAID_CREATIVE);
        }

        $items = $itemsQuery->limit(50)->get();
        $accounts = SocialMediaPlatform::query()
            ->where('user_id', Auth::id())
            ->orderBy('platform')
            ->get();

        return view('social-media::workspace', [
            'section' => $section,
            'profile' => $profile,
            'verticals' => $this->verticalOptions($user),
            'contentTypes' => $contentTypes,
            'selectedContentType' => $selectedContentType,
            'destinations' => $this->destinationMatrix($user, $profile, $selectedContentType),
            'items' => $items,
            'accounts' => $accounts,
            'metrics' => $this->metrics($user),
            'genericFallback' => ($profile['slug'] ?? 'generic-business') === 'generic-business',
        ]);
    }

    public function storeDraft(Request $request): RedirectResponse
    {
        $user = $this->user();
        $validated = $request->validate([
            'content_type' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:10000'],
            'vertical' => ['nullable', 'string', 'max:100'],
            'subtype' => ['nullable', 'string', 'max:120'],
            'payload' => ['nullable', 'array', 'max:80'],
            'payload.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $content_type = (string) $validated['content_type'];
        if ($content_type === DistributionItem::TYPE_SOCIAL_POST) {
            throw ValidationException::withMessages([
                'content_type' => __('Social posts must use the existing Titan Reach post composer.'),
            ]);
        }

        $profile = $this->capabilities->resolveVerticalProfile(
            $validated['vertical'] ?? null,
            $validated['subtype'] ?? null,
            $user
        );
        $supported = array_values((array) ($profile['content_types'] ?? []));

        if (! in_array($content_type, $supported, true)) {
            throw ValidationException::withMessages([
                'content_type' => __('This content type is not available for the resolved business profile.'),
            ]);
        }

        $payload = array_slice((array) ($validated['payload'] ?? []), 0, 80, true);
        $missing = [];
        foreach ((array) ($profile['required_fields'] ?? []) as $field) {
            $field = (string) $field;
            $value = match ($field) {
                'title' => $validated['title'] ?? null,
                'content', 'description' => $validated['content'] ?? null,
                default => Arr::get($payload, $field),
            };

            if ($value === null || $value === '' || $value === []) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'payload' => __('Missing required profile fields: :fields', [
                    'fields' => implode(', ', $missing),
                ]),
            ]);
        }

        DistributionItem::createForUser($user, [
            'content_type' => $content_type,
            'status' => 'draft',
            'approval_status' => 'pending',
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
            'source_type' => 'manual',
            'source_id' => null,
            'payload' => [
                ...$payload,
                'vertical' => (string) ($profile['slug'] ?? 'generic-business'),
                'resolved_subtype' => $profile['resolved_subtype'] ?? null,
                'profile_version' => $profile['profile_version'] ?? null,
                'profile_provenance' => array_values((array) ($profile['profile_provenance'] ?? [])),
            ],
        ]);

        return redirect()
            ->route('dashboard.user.social-media.workspace', [
                'section' => 'distribute',
                'vertical' => $profile['slug'] ?? null,
                'subtype' => $profile['resolved_subtype'] ?? null,
                'content_type' => $content_type,
            ])
            ->with([
                'type' => 'success',
                'message' => __('Titan Reach draft created. Review destination suitability before publishing.'),
            ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            throw new RuntimeException('Authenticated Titan Reach user is required.');
        }

        return $user;
    }

    private function verticalOptions(User $user): array
    {
        $result = [];
        foreach ($this->capabilities->canonicalVerticalSlugs() as $slug) {
            $resolved = $this->capabilities->resolveVerticalProfile($slug, null, $user);
            $result[$slug] = [
                'slug' => $slug,
                'label' => (string) ($resolved['label'] ?? $slug),
                'subtypes' => array_values((array) ($resolved['subtypes'] ?? [])),
            ];
        }

        return $result;
    }

    private function destinationMatrix(User $user, array $profile, string $contentType): array
    {
        $catalogue = require dirname(__DIR__, 3) . '/config/distribution.php';
        $base = (array) ($catalogue['destinations'] ?? []);
        $configured = (array) config('social-media.distribution.destinations', []);
        $definitions = array_replace_recursive($base, $configured);
        $matrix = [];

        foreach ($definitions as $destination => $definition) {
            $suitability = $this->capabilities->suitabilityFor(
                (string) ($profile['slug'] ?? 'generic-business'),
                (string) $destination,
                $contentType,
                $profile['resolved_subtype'] ?? null,
                $user
            );

            $matrix[$destination] = [
                'slug' => (string) $destination,
                'label' => $this->destinationLabel((string) $destination),
                'mode' => (string) ($definition['mode'] ?? DistributionItem::MODE_EXPORT_ONLY),
                'adapter_available' => (bool) ($definition['adapter_available'] ?? false),
                'approval_required' => (bool) ($definition['approval_required'] ?? false),
                'suitability' => (string) ($suitability['suitability'] ?? 'not-applicable'),
                'available' => (bool) ($suitability['available'] ?? false),
                'reason' => $suitability['reason'] ?? null,
                'required_fields' => array_values((array) ($suitability['required_fields'] ?? [])),
            ];
        }

        return $matrix;
    }

    private function destinationLabel(string $destination): string
    {
        return match ($destination) {
            'x' => 'X',
            'ebay' => 'eBay',
            'meta-ads' => 'Meta Ads',
            'google-business-profile' => 'Google Business Profile',
            'facebook-marketplace' => 'Facebook Marketplace',
            default => ucwords(str_replace(['-', '_'], ' ', $destination)),
        };
    }

    private function metrics(User $user): array
    {
        $base = DistributionItem::query()->where('user_id', $user->getKey());

        return [
            'total_items' => (clone $base)->count(),
            'draft_items' => (clone $base)->where('status', 'draft')->count(),
            'approved_items' => (clone $base)->where('approval_status', 'approved')->count(),
            'listing_items' => (clone $base)->whereIn('content_type', self::LISTING_TYPES)->count(),
            'paid_creatives' => (clone $base)->where('content_type', DistributionItem::TYPE_PAID_CREATIVE)->count(),
        ];
    }
}
