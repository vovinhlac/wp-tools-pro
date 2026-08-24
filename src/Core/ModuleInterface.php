<?php
namespace LacVo\WPToolsPro\Core;

interface ModuleInterface {
    public function id(): string;
    public function boot(): void;
}
