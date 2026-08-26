<?php

declare(strict_types=1);

namespace LacVo\WPToolsPro\Tests;

use LacVo\WPToolsPro\Core\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
    public function test_default_registry_contains_expected_modules(): void
    {
        $registry = new ModuleRegistry();
        $registry->registerDefaults();

        $definitions = $registry->all();

        self::assertSame(
            ['security', 'performance', 'seo', 'redirects'],
            array_keys($definitions)
        );
    }

    public function test_default_definitions_expose_reviewable_metadata(): void
    {
        $registry = new ModuleRegistry();
        $registry->registerDefaults();

        foreach ($registry->all() as $definition) {
            self::assertNotEmpty($definition['title']);
            self::assertNotEmpty($definition['desc']);
            self::assertNotEmpty($definition['category']);
            self::assertNotEmpty($definition['class']);
        }
    }
}
