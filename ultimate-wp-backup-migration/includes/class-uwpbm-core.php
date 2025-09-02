<?php
/**
 * Core plugin class
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin core class
 */
class UWPBM_Core {
    
    /**
     * Plugin instance
     *
     * @var UWPBM_Core
     */
    private static $instance = null;
    
    /**
     * Plugin components
     *
     * @var array
     */
    private $components = [];
    
    /**
     * Get plugin instance
     *
     * @return UWPBM_Core
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Load plugin components
     */
    private function load_components() {
        // Load admin interface
        if (is_admin()) {
            $this->components['admin'] = new UWPBM_Admin();
            $this->components['wizard'] = new UWPBM_Wizard();
        }
        
        // Load CLI commands if WP-CLI is available
        if (defined('WP_CLI') && WP_CLI) {
            $this->components['cli'] = new UWPBM_CLI();
        }
        
        // Load advanced features
        $this->components['scheduler'] = new UWPBM_Scheduler();
        $this->components['monitor'] = new UWPBM_Monitor();
        
        // Schedule cleanup tasks
        if (!wp_next_scheduled('uwpbm_cleanup')) {
            wp_schedule_event(time(), 'daily', 'uwpbm_cleanup');
        }
        add_action('uwpbm_cleanup', ['UWPBM_Performance', 'cleanup_temp_files']);
        add_action('uwpbm_cleanup', ['UWPBM_Performance', 'cleanup_old_backups']);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ultimate-wp-backup-migration',
            false,
            dirname(UWPBM_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'uwpbm') === false) {
            return;
        }
        
        wp_enqueue_style(
            'uwpbm-admin',
            UWPBM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            UWPBM_VERSION
        );
        
        wp_enqueue_script(
            'uwpbm-admin',
            UWPBM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            UWPBM_VERSION,
            true
        );
        
        wp_localize_script('uwpbm-admin', 'uwpbm_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('uwpbm_ajax'),
        ]);
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Clear any cached data
        wp_cache_flush();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('uwpbm_scheduled_backup');
        wp_clear_scheduled_hook('uwpbm_monitor_check');
        
        // Clear scheduled backup events
        $schedules = get_option('uwpbm_backup_schedules', []);
        foreach ($schedules as $schedule) {
            wp_clear_scheduled_hook('uwpbm_scheduled_backup_' . $schedule['id']);
        }
        
        // Clear any cached data
        wp_cache_flush();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Backup history table
        $table_name = $wpdb->prefix . 'uwpbm_backups';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            status varchar(50) NOT NULL,
            size bigint(20) DEFAULT 0,
            location text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Settings table
        $table_name = $wpdb->prefix . 'uwpbm_settings';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = [
            'uwpbm_version' => UWPBM_VERSION,
            'uwpbm_max_execution_time' => 300,
            'uwpbm_memory_limit' => '512M',
            'uwpbm_chunk_size' => 5242880, // 5MB
            'uwpbm_backup_retention' => 30, // days
            'uwpbm_enable_logging' => true,
            'uwpbm_monitor_enabled' => false,
            'uwpbm_monitor_interval' => 3600,
            'uwpbm_wizard_completed' => false,
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Get plugin option
     *
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get_option($key, $default = null) {
        return get_option($key, $default);
    }
    
    /**
     * Update plugin option
     *
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool
     */
    public static function update_option($key, $value) {
        return update_option($key, $value);
    }
}