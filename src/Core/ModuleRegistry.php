<?php
namespace LacVo\WPToolsPro\Core;

use LacVo\WPToolsPro\Modules\{PerformanceModule,RedirectModule,SecurityModule,SeoModule};

if (!defined('ABSPATH')) { exit; }

final class ModuleRegistry {
    private array $definitions = [];
    private array $instances = [];

    public function registerDefaults(): void {
        $this->definitions = [
            'security' => [
                'title' => 'Security & Core',
                'desc' => 'Conservative hardening for information exposure and risky defaults.',
                'category' => 'Security',
                'class' => SecurityModule::class,
            ],
            'performance' => [
                'title' => 'Performance',
                'desc' => 'Remove low-value front-end payloads with conservative defaults.',
                'category' => 'Performance',
                'class' => PerformanceModule::class,
            ],
            'seo' => [
                'title' => 'Permalinks & SEO',
                'desc' => 'Technical SEO housekeeping and attachment redirect behavior.',
                'category' => 'SEO',
                'class' => SeoModule::class,
            ],
            'redirects' => [
                'title' => 'Redirects & 404 Monitor',
                'desc' => 'Safe redirects, loop protection, hit analytics and 404 logging.',
                'category' => 'SEO',
                'class' => RedirectModule::class,
            ],
        ];
    }

    public function all(): array { return $this->definitions; }

    public function bootEnabled(): void {
        foreach ($this->definitions as $id => $definition) {
            if (!Settings::isEnabled($id)) { continue; }
            $class = $definition['class'];
            if (!class_exists($class)) { continue; }
            $instance = new $class();
            if ($instance instanceof ModuleInterface) {
                $instance->boot();
                $this->instances[$id] = $instance;
            }
        }
    }

    public function isEnabled(string $id): bool { return Settings::isEnabled($id); }
}
