<?php
/**
 * Plugin Name: WP Tools Pro
 * Plugin URI: https://vovinhlac.com/
 * Description: Modular WordPress administration, content, media, SEO, performance, security, privacy and email toolkit.
 * Version: 1.7.0
 * Author: Vo Vinh Lac
 * Author URI: https://vovinhlac.com/
 * Text Domain: wp-tools-pro
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) { exit; }

define('LACVO_WTP_VERSION', '1.7.0');
define('LACVO_WTP_FILE', __FILE__);
define('LACVO_WTP_DIR', plugin_dir_path(__FILE__));
define('LACVO_WTP_URL', plugin_dir_url(__FILE__));
define('LACVO_WTP_BASENAME', plugin_basename(__FILE__));

spl_autoload_register(static function ($class) {
    $prefix = 'LacVo\\WPToolsPro\\';
    if (strpos($class, $prefix) !== 0) { return; }
    $relative = substr($class, strlen($prefix));
    $file = LACVO_WTP_DIR . 'src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) { require_once $file; }
});

register_activation_hook(__FILE__, ['LacVo\\WPToolsPro\\Core\\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['LacVo\\WPToolsPro\\Core\\Plugin', 'deactivate']);

add_action('plugins_loaded', static function () {
    LacVo\WPToolsPro\Core\Plugin::instance()->boot();
});
