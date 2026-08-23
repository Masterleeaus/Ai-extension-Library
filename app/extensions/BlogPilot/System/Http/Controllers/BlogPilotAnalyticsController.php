<?php

declare(strict_types=1);

namespace App\Extensions\BlogPilot\System\Http\Controllers;

use App\Extensions\BlogPilot\System\Models\BlogPilot;
use App\Extensions\BlogPilot\System\Models\BlogPilotPost;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogPilotAnalyticsController extends Controller
{
    public function __invoke(): View
    {
        $userId = Auth::id();

        $baseQuery = BlogPilotPost::query()
            ->where('user_id', $userId);

        $stats = [
            'total_posts' => (clone $baseQuery)->count(),

            'draft_posts' => (clone $baseQuery)
                ->where('status', BlogPilotPost::STATUS_DRAFT)
                ->count(),

            'scheduled_posts' => (clone $baseQuery)
                ->where('status', BlogPilotPost::STATUS_SCHEDULED)
                ->count(),

            'published_posts' => (clone $baseQuery)
                ->where('status', BlogPilotPost::STATUS_PUBLISHED)
                ->count(),

            'created_today' => (clone $baseQuery)
                ->whereDate('created_at', today())
                ->count(),
        ];

        $agents = BlogPilot::query()->where('user_id', $userId)->get();

        $monthRange = $this->buildMonthRange(12);
        $publishedChartData = $this->buildPublishedPostsChartData($userId, $agents, $monthRange);
        $newsFeed = $this->buildAnalyticsNews($userId, $stats);

        return view('blogpilot::analytics.index', [
            'stats'              => $stats,
            'agents'             => $agents,
            'news'               => $newsFeed,
            'publishedChartData' => $publishedChartData,
            'publishedMonths'    => $monthRange,
        ]);
    }

    private function buildAnalyticsNews(int $userId, array $stats): array
    {
        $items = [];

        if (($stats['created_today'] ?? 0) > 0) {
            $items[] = __(':count posts were created today.', ['count' => $stats['scheduled_posts']]);
        }

        if (($stats['total_posts'] ?? 0) > 0) {
            $items[] = __('Total of :count posts are created.', ['count' => $stats['total_posts']]);
        }

        if (($stats['published_posts'] ?? 0) > 0) {
            $items[] = __('Total of :count posts were published.', ['count' => $stats['published_posts']]);
        }

        if (($stats['scheduled_posts'] ?? 0) > 0) {
            $items[] = __('Total of :count posts were scheduled.', ['count' => $stats['scheduled_posts']]);
        }

        $recentPost = BlogPilotPost::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if ($recentPost) {
            $items[] = __('The last post was created :date.', ['date' => optional($recentPost->created_at)->diffForHumans()]);
        }

        return $items;
    }

    private function buildMonthRange(int $months = 12): array
    {
        $months = max(1, $months);
        $range = [];

        $cursor = now()->copy()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $range[] = $cursor->copy();
            $cursor->addMonth();
        }

        return $range;
    }

    private function buildPublishedPostsChartData(int $userId, $agents, array $months): array
    {
        [$months, $monthKeys, $startDate, $endDate] = $this->prepareMonthMetadata($months);

        $records = BlogPilotPost::query()
            ->where('user_id', $userId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->get();

        $recordsByAgent = $records->groupBy('agent_id');

        $todayTotals = BlogPilotPost::query()
            ->where('user_id', $userId)
            ->whereNotNull('agent_id')
            ->whereDate('scheduled_at', now()->toDateString())
            ->selectRaw('agent_id, COUNT(*) as total')
            ->groupBy('agent_id')
            ->pluck('total', 'agent_id');

        return $this->buildChartSeriesFromRecords(
            $agents,
            $monthKeys,
            $recordsByAgent,
            $todayTotals,
            'today_posts'
        );
    }

    private function prepareMonthMetadata(array $months): array
    {
        if (empty($months)) {
            $months = $this->buildMonthRange(12);
        }

        $normalizedMonths = collect($months)
            ->map(fn ($month) => $month instanceof Carbon ? $month->copy() : Carbon::parse($month))
            ->values();

        $monthKeys = $normalizedMonths->map(fn (Carbon $month) => $month->format('Y-m'))->values();

        return [
            $normalizedMonths->all(),
            $monthKeys->all(),
            $normalizedMonths->first()->copy(),
            $normalizedMonths->last()->copy()->endOfMonth(),
        ];
    }

    private function buildChartSeriesFromRecords($agents, array $monthKeys, $recordsByAgent, $currentTotals, string $statKey, bool $asFloat = false): array
    {
        $allSeries = array_fill(0, count($monthKeys), 0);
        $chartData = [];

        foreach ($agents as $agent) {
            $agentId = $agent->id;
            $agentName = $agent->name ?: ('agent_' . $agentId);

            $agentData = $recordsByAgent
                ->get($agentId, collect())
                ->groupBy(function ($row) {
                    return Carbon::parse($row->scheduled_at)->format('Y-m');
                })
                ->map(function ($rows) {
                    return [
                        'total' => $rows->count(),
                    ];
                });

            $seriesData = [];

            foreach ($monthKeys as $index => $key) {
                $value = (float) data_get($agentData->get($key), 'total', 0);
                $seriesData[] = $value;
                $allSeries[$index] += $value;
            }

            $chartData[] = [
                'label'        => Str::headline($agentName),
                'id'           => $agentId,
                'chart_series' => [
                    'name'   => $agentName,
                    'data'   => $seriesData,
                    'hidden' => true,
                ],
                $statKey      => $asFloat
                    ? round((float) ($currentTotals[$agentId] ?? 0), 2)
                    : (int) ($currentTotals[$agentId] ?? 0),
            ];
        }

        $chartData = array_values($chartData);
        array_unshift($chartData, [
            'label'        => __('All'),
            'id'           => '*',
            'chart_series' => [
                'name' => 'all',
                'data' => $allSeries,
            ],
            $statKey       => $asFloat
                ? round((float) collect($currentTotals)->sum(), 2)
                : (int) collect($currentTotals)->sum(),
        ]);

        return $chartData;
    }
}
