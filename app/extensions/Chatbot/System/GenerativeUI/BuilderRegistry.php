<?php

namespace App\Extensions\Chatbot\System\GenerativeUI;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class BuilderRegistry
{
    public function __construct(private readonly ?string $basePath = null)
    {
    }

    public function manifest(): array
    {
        return $this->read('manifest.json');
    }

    public function all(string $collection): array
    {
        $this->assertCollection($collection);
        $directory = $this->root().DIRECTORY_SEPARATOR.$collection;

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(fn ($file) => $this->decode(File::get($file->getPathname()), $file->getPathname()))
            ->sortBy(fn (array $item) => [$item['category'] ?? '', $item['name'] ?? $item['id'] ?? ''])
            ->values()
            ->all();
    }

    public function find(string $collection, string $slug): ?array
    {
        $this->assertCollection($collection);
        $safeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        if ($safeSlug !== $slug) {
            return null;
        }

        $path = $collection.'/'.$safeSlug.'.json';
        $absolute = $this->root().DIRECTORY_SEPARATOR.$path;

        return File::isFile($absolute) ? $this->read($path) : null;
    }

    public function mockData(string $slug): array
    {
        return $this->find('mock-data', $slug) ?? [];
    }

    public function catalogue(): array
    {
        return [
            'manifest' => $this->manifest(),
            'components' => $this->all('components'),
            'blocks' => $this->all('blocks'),
            'pages' => $this->all('pages'),
            'themes' => $this->all('themes'),
            'specs' => $this->all('specs'),
            'surface_profiles' => $this->manifest()['surface_profiles'] ?? [],
        ];
    }


    /** @return array<string, array> */
    public function componentMap(): array
    {
        return collect($this->all('components'))
            ->filter(fn (array $component): bool => isset($component['id']) && is_string($component['id']))
            ->keyBy('id')
            ->all();
    }

    /** @return array<int, string> */
    public function componentIds(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $component): ?string => isset($component['id']) && is_string($component['id']) ? $component['id'] : null,
            $this->all('components')
        )));
    }

    /** @return array<int, string> */
    public function actionIds(): array
    {
        return array_values(array_filter($this->manifest()['allowed_actions'] ?? [], 'is_string'));
    }

    public function generativeUiCatalogue(): array
    {
        return [
            'version' => '1.1',
            'authority' => 'presentation-only',
            'components' => $this->all('components'),
            'actions' => $this->actionIds(),
            'specs' => $this->all('specs'),
            'surface_profiles' => $this->manifest()['surface_profiles'] ?? [],
        ];
    }

    public function resolveSectionData(array $section, array $mockData): mixed
    {
        $source = $section['data_source'] ?? null;
        return $source ? Arr::get($mockData, $source, []) : ($section['data'] ?? []);
    }

    private function read(string $relativePath): array
    {
        $absolute = $this->root().DIRECTORY_SEPARATOR.$relativePath;
        if (! File::isFile($absolute)) {
            throw new RuntimeException("Builder definition not found: {$relativePath}");
        }

        return $this->decode(File::get($absolute), $absolute);
    }

    private function decode(string $json, string $source): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid builder JSON: {$source}");
        }

        return $decoded;
    }

    private function root(): string
    {
        return $this->basePath ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'builder';
    }

    private function assertCollection(string $collection): void
    {
        if (! in_array($collection, ['components', 'blocks', 'pages', 'themes', 'mock-data', 'specs'], true)) {
            throw new RuntimeException("Unsupported builder collection: {$collection}");
        }
    }
}
