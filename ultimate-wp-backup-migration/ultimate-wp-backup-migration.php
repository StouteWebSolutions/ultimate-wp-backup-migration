<?php
/**
 * Plugin Name: Ultimate WordPress Backup Migration
 * Plugin URI: https://wordpress.org/plugins/ultimate-wp-backup-migration/
 * Description: The ultimate WordPress migration and backup solution with unlimited file sizes, multiple protocols, and complete local control.
 * Version: 1.0.0
 * Author: Stoute Web Solutions Team
 * Author URI: https://github.com/StouteWebSolutions/ultimate-wp-backup-migration
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: ultimate-wp-backup-migration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UWPBM_VERSION', '1.0.0');
define('UWPBM_PLUGIN_FILE', __FILE__);
define('UWPBM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UWPBM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UWPBM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'UWPBM_') !== 0) {
        return;
    }
    
    $class_file = str_replace('_', '-', strtolower($class));
    $file = UWPBM_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize plugin
function uwpbm_init() {
    if (class_exists('UWPBM_Core')) {
        UWPBM_Core::instance();
    }
}
add_action('plugins_loaded', 'uwpbm_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    if (class_exists('UWPBM_Core')) {
        UWPBM_Core::activate();
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    if (class_exists('UWPBM_Core')) {
        UWPBM_Core::deactivate();
    }
});