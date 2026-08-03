<?php

namespace App\Extensions\Chatbot\Titan;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use App\Extensions\Chatbot\System\TitanShell\TemplateNavigation;

/**
 * Titan Suite Registry
 * 
 * Loads and manages all 10 vertical app templates
 * Each template extends the Chatbot Extension with specific configurations
 */
class TitanRegistry
{
    /**
     * @var Collection App templates
     */
    protected static Collection $templates;

    /**
     * @var string Templates directory
     */
    protected static string $templatesPath;

    /**
     * Initialize registry
     */
    public static function init(): void
    {
        static::$templatesPath = resource_path('titan-apps/titan-suite-templates');
        static::$templates = new Collection();
        static::loadTemplates();
    }

    /**
     * Load all template configs
     */
    protected static function loadTemplates(): void
    {
        $files = new Filesystem();
        
        $templateDirs = $files->directories(static::$templatesPath);
        
        foreach ($templateDirs as $dir) {
            $configFile = $dir . '/config.json';
            
            if ($files->exists($configFile)) {
                $config = json_decode(
                    $files->get($configFile),
                    true
                );
                
                if ($config && isset($config['slug'])) {
                    static::$templates->put($config['slug'], $config);
                }
            }
        }
    }

    /**
     * Get all templates
     */
    public static function all(): Collection
    {
        if (static::$templates->isEmpty()) {
            static::init();
        }
        return static::$templates;
    }

    /**
     * Get specific template by slug
     */
    public static function get(string $slug): ?array
    {
        if (static::$templates->isEmpty()) {
            static::init();
        }
        return static::$templates->get($slug);
    }

    /**
     * List all template slugs
     */
    public static function list(): array
    {
        return static::all()->keys()->toArray();
    }

    /**
     * Check if template exists
     */
    public static function has(string $slug): bool
    {
        return static::all()->has($slug);
    }

    /**
     * Get template by type
     */
    public static function byType(string $type): Collection
    {
        return static::all()
            ->filter(fn($t) => ($t['type'] ?? null) === $type);
    }

    /**
     * Get chatbot config for template
     */
    public static function getChatbot(string $slug): ?array
    {
        $template = static::get($slug);
        return $template['chatbot'] ?? null;
    }

    /**
     * Get features for template
     */
    public static function getFeatures(string $slug): array
    {
        $template = static::get($slug);
        return $template['features'] ?? [];
    }

    /**
     * Get API routes for template
     */
    public static function getRoutes(string $slug): array
    {
        $template = static::get($slug);
        return $template['api_routes'] ?? [];
    }

    /**
     * Get required permissions for template
     */
    public static function getPermissions(string $slug): array
    {
        $template = static::get($slug);
        return $template['permissions'] ?? [];
    }

    /** Get template-aware shell navigation. */
    public static function getNavigation(string $slug): array
    {
        return TemplateNavigation::resolve($slug);
    }

    /**
     * Create instance from template
     */
    public static function create(string $slug, array $config = []): ?object
    {
        $template = static::get($slug);
        
        if (!$template) {
            return null;
        }

        return new TitanApp(
            slug: $slug,
            template: $template,
            config: $config
        );
    }

    /**
     * Get all templates as array (for API)
     */
    public static function toArray(): array
    {
        return static::all()
            ->map(function($config, $slug) {
                return [
                    'slug' => $slug,
                    'name' => $config['name'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'features' => $config['features'] ?? [],
                ];
            })
            ->values()
            ->toArray();
    }
}

/**
 * Titan App Instance
 * 
 * Represents a single app instance created from a template
 */
class TitanApp
{
    protected string $slug;
    protected array $template;
    protected array $config;

    public function __construct(string $slug, array $template, array $config = [])
    {
        $this->slug = $slug;
        $this->template = $template;
        $this->config = array_merge($template, $config);
    }

    /**
     * Get app slug
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * Get app name
     */
    public function name(): string
    {
        return $this->config['name'] ?? $this->slug;
    }

    /**
     * Get app config
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * Get chatbot instance config
     */
    public function chatbotConfig(): array
    {
        return $this->config['chatbot'] ?? [];
    }

    /**
     * Get UI config
     */
    public function uiConfig(): array
    {
        return [
            'name' => $this->name(),
            'icon' => $this->config['icon'] ?? '⚙️',
            'color' => $this->config['color'] ?? '#00d4ff',
            'theme' => $this->config['theme'] ?? [],
            'features' => $this->config['features'] ?? [],
        ];
    }

    /**
     * Get install URL
     */
    public function installUrl(): string
    {
        return route('api.v2.titan.install', ['app' => $this->slug]);
    }

    /**
     * Get manifest for PWA
     */
    public function manifest(): array
    {
        return [
            'name' => $this->name(),
            'short_name' => strtoupper(substr($this->slug, 6)),
            'description' => $this->config['description'] ?? '',
            'start_url' => $this->installUrl(),
            'scope' => '/titan/' . $this->slug,
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color' => $this->config['color'] ?? '#00d4ff',
            'icons' => [
                [
                    'src' => '/images/titan-' . $this->slug . '-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/images/titan-' . $this->slug . '-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ];
    }
}
