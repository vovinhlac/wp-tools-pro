<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class Settings {
    public static function modules(): array {
        $value = get_option('lacvo_wtp_modules', []);
        return is_array($value) ? $value : [];
    }
    public static function isEnabled(string $id): bool { $modules=self::modules(); return !empty($modules[$id]); }
    public static function all(): array { $value=get_option('lacvo_wtp_settings',[]); return is_array($value)?$value:[]; }
    public static function advanced(): array { $value=get_option('lacvo_wtp_advanced',[]); return is_array($value)?$value:[]; }
    public static function get(string $key, $default = null) {
        $all=self::all(); if(array_key_exists($key,$all))return $all[$key];
        $advanced=self::advanced(); return $advanced[$key]??$default;
    }
}
