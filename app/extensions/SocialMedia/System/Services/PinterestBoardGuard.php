<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use InvalidArgumentException;

class PinterestBoardGuard
{
    public function filterVisibleBoards(array $boards): array
    {
        return array_values(array_filter(array_map(
            static fn ($board): array => (array) $board,
            $boards
        ), static function (array $board): bool {
            $id = trim((string) ($board['id'] ?? ''));
            $privacy = strtoupper(trim((string) ($board['privacy'] ?? 'PUBLIC')));

            return $id !== '' && $privacy !== 'SECRET';
        }));
    }

    public function synchroniseDiscovery(
        SocialMediaPlatform $account,
        array $result
    ): array {
        $boards = $this->mergeBoards(
            (array) data_get($account->credentials, 'boards', []),
            (array) ($result['boards'] ?? [])
        );
        $providerCapabilities = (array) ($result['provider_capabilities'] ?? []);
        $providerCapabilities['publish'] = (bool) ($providerCapabilities['publish'] ?? false)
            && $boards !== [];
        $result['boards'] = $boards;
        $result['provider_capabilities'] = $providerCapabilities;
        $result['ready'] = (bool) ($providerCapabilities['publish'] ?? false);

        $credentials = (array) $account->credentials;
        $credentials['boards'] = $boards;
        $credentials['provider_capabilities'] = $providerCapabilities;
        data_set($credentials, 'health.boards_count', count($boards));
        data_set($credentials, 'health.status', $result['ready'] ? 'healthy' : 'limited');
        $account->update(['credentials' => $credentials]);
        $account->refresh();

        return $result;
    }

    public function synchroniseBoards(
        SocialMediaPlatform $account,
        array $result
    ): array {
        $boards = $this->mergeBoards(
            (array) data_get($account->credentials, 'boards', []),
            (array) ($result['boards'] ?? [])
        );
        $result['boards'] = $boards;
        $credentials = (array) $account->credentials;
        $credentials['boards'] = $boards;
        data_set($credentials, 'health.boards_count', count($boards));
        $account->update(['credentials' => $credentials]);
        $account->refresh();

        return $result;
    }

    private function mergeBoards(array $existing, array $incoming): array
    {
        $merged = [];

        foreach ($this->filterVisibleBoards([...$existing, ...$incoming]) as $board) {
            $merged[(string) $board['id']] = $board;
        }

        return array_values($merged);
    }

    public function assertWritableBoard(
        SocialMediaPlatform $account,
        string $boardId
    ): array {
        $boardId = trim($boardId);

        if ($boardId === '') {
            throw new InvalidArgumentException('pinterest_board_not_authorized');
        }

        $board = collect((array) data_get($account->credentials, 'boards', []))
            ->map(static fn ($item): array => (array) $item)
            ->first(static fn (array $item): bool => (string) ($item['id'] ?? '') === $boardId);

        if (! is_array($board)) {
            throw new InvalidArgumentException('pinterest_board_not_authorized');
        }

        if (strtoupper(trim((string) ($board['privacy'] ?? 'PUBLIC'))) === 'SECRET') {
            throw new InvalidArgumentException('secret_board_not_publishable');
        }

        return $board;
    }
}
