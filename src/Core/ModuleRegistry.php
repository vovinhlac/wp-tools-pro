<?php
namespace LacVo\WPToolsPro\Core;

use LacVo\WPToolsPro\Modules\{AdminModule,EditorModule,TocModule,DuplicateModule,MediaModule,CommentsModule,BrandingModule,PermissionsModule,SecurityModule,PerformanceModule,SeoModule,SnippetsModule,ConsentModule,SmtpModule,DeveloperModule,RedirectModule};

if (!defined('ABSPATH')) { exit; }

final class ModuleRegistry {
    private array $definitions = [];
    private array $instances = [];

    public function registerDefaults(): void {
        $this->definitions = [
            'admin' => ['title'=>'Admin Experience','desc'=>'Clean and simplify the WordPress administration experience.','category'=>'Administration','icon'=>'dashicons-dashboard','class'=>AdminModule::class],
            'editor' => ['title'=>'Posts & Editor','desc'=>'Editor preferences and content workflow enhancements.','category'=>'Content','icon'=>'dashicons-edit-page','class'=>EditorModule::class],
            'toc' => ['title'=>'Table of Contents','desc'=>'Automatic accessible table of contents with stable heading anchors.','category'=>'Content','icon'=>'dashicons-list-view','class'=>TocModule::class],
            'duplicate' => ['title'=>'Duplicate Content','desc'=>'Clone posts, pages and supported custom post types safely.','category'=>'Content','icon'=>'dashicons-admin-page','class'=>DuplicateModule::class],
            'media' => ['title'=>'Images & Media','desc'=>'Upload hygiene, image metadata and safer SVG handling.','category'=>'Media','icon'=>'dashicons-format-image','class'=>MediaModule::class],
            'comments' => ['title'=>'Comments & Anti-Spam','desc'=>'Comment hardening, URL limits and keyword screening.','category'=>'Security','icon'=>'dashicons-admin-comments','class'=>CommentsModule::class],
            'branding' => ['title'=>'Branding & Login','desc'=>'Customize login branding and selected WordPress labels.','category'=>'Administration','icon'=>'dashicons-admin-customizer','class'=>BrandingModule::class],
            'permissions' => ['title'=>'Roles & Menus','desc'=>'Reduce selected admin menus for non-administrator roles.','category'=>'Administration','icon'=>'dashicons-groups','class'=>PermissionsModule::class],
            'security' => ['title'=>'Security & Core','desc'=>'Safe hardening for information exposure and risky defaults.','category'=>'Security','icon'=>'dashicons-lock','class'=>SecurityModule::class],
            'performance' => ['title'=>'Performance','desc'=>'Disable low-value front-end payloads using conservative defaults.','category'=>'Performance','icon'=>'dashicons-performance','class'=>PerformanceModule::class],
            'seo' => ['title'=>'Permalinks & SEO','desc'=>'Attachment redirects and technical SEO housekeeping.','category'=>'SEO','icon'=>'dashicons-chart-line','class'=>SeoModule::class],
            'redirects' => ['title'=>'Redirects & 404 Monitor','desc'=>'Manage safe redirects and track recurring not-found URLs.','category'=>'SEO','icon'=>'dashicons-randomize','class'=>RedirectModule::class],
            'snippets' => ['title'=>'Code & CSS','desc'=>'Safely insert verified HTML/CSS snippets in selected locations.','category'=>'Developer','icon'=>'dashicons-editor-code','class'=>SnippetsModule::class],
            'consent' => ['title'=>'Cookie Consent','desc'=>'Lightweight cookie preference banner with configurable copy.','category'=>'Privacy','icon'=>'dashicons-shield-alt','class'=>ConsentModule::class],
            'smtp' => ['title'=>'SMTP Email','desc'=>'Configure SMTP transport and run a delivery test from the settings page.','category'=>'Email','icon'=>'dashicons-email','class'=>SmtpModule::class],
            'developer' => ['title'=>'Developer Tools','desc'=>'Environment visibility and selected diagnostics for administrators.','category'=>'Developer','icon'=>'dashicons-admin-tools','class'=>DeveloperModule::class],
        ];
    }

    public function all(): array { return $this->definitions; }

    public function bootEnabled(): void {
        foreach ($this->definitions as $id => $definition) {
            if (!Settings::isEnabled($id)) { continue; }
            $class = $definition['class'];
            if (class_exists($class)) {
                $instance = new $class();
                if ($instance instanceof ModuleInterface) {
                    $instance->boot();
                    $this->instances[$id] = $instance;
                }
            }
        }
    }

    public function isEnabled(string $id): bool { return Settings::isEnabled($id); }
}
