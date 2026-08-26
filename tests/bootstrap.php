<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

require_once dirname(__DIR__) . '/src/Core/ModuleInterface.php';
require_once dirname(__DIR__) . '/src/Core/Settings.php';
require_once dirname(__DIR__) . '/src/Modules/SecurityModule.php';
require_once dirname(__DIR__) . '/src/Modules/PerformanceModule.php';
require_once dirname(__DIR__) . '/src/Modules/SeoModule.php';
require_once dirname(__DIR__) . '/src/Modules/RedirectModule.php';
require_once dirname(__DIR__) . '/src/Core/ModuleRegistry.php';
