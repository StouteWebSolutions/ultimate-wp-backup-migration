<?php
/**
 * Admin interface class
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface handler
 */
class UWPBM_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_uwpbm_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_uwpbm_start_backup', [$this, 'ajax_start_backup']);
        add_action('wp_ajax_uwpbm_backup_progress', [$this, 'ajax_backup_progress']);
        add_action('wp_ajax_uwpbm_create_schedule', [$this, 'ajax_create_schedule']);
        add_action('wp_ajax_uwpbm_delete_schedule', [$this, 'ajax_delete_schedule']);
        add_action('wp_ajax_uwpbm_toggle_monitor', [$this, 'ajax_toggle_monitor']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Ultimate Backup Migration', 'ultimate-wp-backup-migration'),
            __('Backup Migration', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-dashboard',
            [$this, 'dashboard_page'],
            'dashicons-migrate',
            30
        );
        
        add_submenu_page(
            'uwpbm-dashboard',
            __('Dashboard', 'ultimate-wp-backup-migration'),
            __('Dashboard', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-dashboard',
            [$this, 'dashboard_page']
        );
        
        add_submenu_page(
            'uwpbm-dashboard',
            __('Export', 'ultimate-wp-backup-migration'),
            __('Export', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-export',
            [$this, 'export_page']
        );
        
        add_submenu_page(
            'uwpbm-dashboard',
            __('Import', 'ultimate-wp-backup-migration'),
            __('Import', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-import',
            [$this, 'import_page']
        );
        
        add_submenu_page(
            'uwpbm-dashboard',
            __('Schedules', 'ultimate-wp-backup-migration'),
            __('Schedules', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-schedules',
            [$this, 'schedules_page']
        );
        
        add_submenu_page(
            'uwpbm-dashboard',
            __('Settings', 'ultimate-wp-backup-migration'),
            __('Settings', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $this->render_template('dashboard', [
            'recent_backups' => $this->get_recent_backups(),
            'storage_usage' => $this->get_storage_usage(),
            'next_scheduled' => $this->get_next_scheduled_backup(),
        ]);
    }
    
    /**
     * Export page
     */
    public function export_page() {
        $this->render_template('export', [
            'protocols' => $this->get_available_protocols(),
            'export_options' => $this->get_export_options(),
        ]);
    }
    
    /**
     * Import page
     */
    public function import_page() {
        $this->render_template('import', [
            'protocols' => $this->get_available_protocols(),
            'available_backups' => $this->get_available_backups(),
        ]);
    }
    
    /**
     * Schedules page
     */
    public function schedules_page() {
        $scheduler = new UWPBM_Scheduler();
        $monitor = new UWPBM_Monitor();
        
        $this->render_template('schedules', [
            'schedules' => $scheduler->get_schedules(),
            'monitor_enabled' => get_option('uwpbm_monitor_enabled', false),
            'monitor_log' => $monitor->get_monitor_log(),
        ]);
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'uwpbm_settings')) {
            $this->save_settings();
        }
        
        $this->render_template('settings', [
            'settings' => $this->get_current_settings(),
        ]);
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('uwpbm_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $protocol = sanitize_text_field($_POST['protocol'] ?? '');
        $settings = $_POST['settings'] ?? [];
        
        // Sanitize settings
        array_walk_recursive($settings, 'sanitize_text_field');
        
        try {
            $protocol_handler = $this->get_protocol_handler($protocol);
            $result = $protocol_handler->test_connection($settings);
            
            wp_send_json_success([
                'message' => __('Connection successful!', 'ultimate-wp-backup-migration'),
                'details' => $result,
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * AJAX: Start backup
     */
    public function ajax_start_backup() {
        check_ajax_referer('uwpbm_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $options = $_POST['options'] ?? [];
        array_walk_recursive($options, 'sanitize_text_field');
        
        try {
            $migrator = new UWPBM_Migrator();
            $backup_id = $migrator->start_export($options);
            
            wp_send_json_success([
                'backup_id' => $backup_id,
                'message' => __('Backup started successfully', 'ultimate-wp-backup-migration'),
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * AJAX: Get backup progress
     */
    public function ajax_backup_progress() {
        check_ajax_referer('uwpbm_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $backup_id = intval($_POST['backup_id'] ?? 0);
        
        if (!$backup_id) {
            wp_send_json_error(['message' => __('Invalid backup ID', 'ultimate-wp-backup-migration')]);
        }
        
        $progress = $this->get_backup_progress($backup_id);
        wp_send_json_success($progress);
    }
    
    /**
     * Render admin template
     *
     * @param string $template Template name
     * @param array $vars Template variables
     */
    private function render_template($template, $vars = []) {
        extract($vars);
        $template_file = UWPBM_PLUGIN_DIR . "templates/admin-{$template}.php";
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . 
                 sprintf(__('Template not found: %s', 'ultimate-wp-backup-migration'), $template) . 
                 '</p></div>';
        }
    }
    
    /**
     * Get available protocols
     *
     * @return array
     */
    private function get_available_protocols() {
        return [
            'local' => __('Local File System', 'ultimate-wp-backup-migration'),
            'ftp' => __('FTP', 'ultimate-wp-backup-migration'),
            'sftp' => __('SFTP', 'ultimate-wp-backup-migration'),
        ];
    }
    
    /**
     * Get export options
     *
     * @return array
     */
    private function get_export_options() {
        return [
            'include_database' => true,
            'include_media' => true,
            'include_plugins' => true,
            'include_themes' => true,
            'include_uploads' => true,
        ];
    }
    
    /**
     * Get recent backups
     *
     * @return array
     */
    private function get_recent_backups() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups 
             ORDER BY created_at DESC 
             LIMIT 10"
        );
    }
    
    /**
     * Get storage usage
     *
     * @return array
     */
    private function get_storage_usage() {
        // Placeholder - implement actual storage calculation
        return [
            'used' => '2.5 GB',
            'available' => '10 GB',
            'percentage' => 25,
        ];
    }
    
    /**
     * Get next scheduled backup
     *
     * @return string|null
     */
    private function get_next_scheduled_backup() {
        $timestamp = wp_next_scheduled('uwpbm_scheduled_backup');
        return $timestamp ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp) : null;
    }
    
    /**
     * Get available backups
     *
     * @return array
     */
    private function get_available_backups() {
        // Placeholder - implement actual backup discovery
        return [];
    }
    
    /**
     * Get current settings
     *
     * @return array
     */
    private function get_current_settings() {
        return [
            'max_execution_time' => UWPBM_Core::get_option('uwpbm_max_execution_time', 300),
            'memory_limit' => UWPBM_Core::get_option('uwpbm_memory_limit', '512M'),
            'chunk_size' => UWPBM_Core::get_option('uwpbm_chunk_size', 5242880),
            'backup_retention' => UWPBM_Core::get_option('uwpbm_backup_retention', 30),
            'enable_logging' => UWPBM_Core::get_option('uwpbm_enable_logging', true),
        ];
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $settings = [
            'uwpbm_max_execution_time' => intval($_POST['max_execution_time'] ?? 300),
            'uwpbm_memory_limit' => sanitize_text_field($_POST['memory_limit'] ?? '512M'),
            'uwpbm_chunk_size' => intval($_POST['chunk_size'] ?? 5242880),
            'uwpbm_backup_retention' => intval($_POST['backup_retention'] ?? 30),
            'uwpbm_enable_logging' => !empty($_POST['enable_logging']),
        ];
        
        foreach ($settings as $key => $value) {
            UWPBM_Core::update_option($key, $value);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . 
                 __('Settings saved successfully!', 'ultimate-wp-backup-migration') . 
                 '</p></div>';
        });
    }
    
    /**
     * Get protocol handler
     *
     * @param string $protocol Protocol name
     * @return object
     */
    private function get_protocol_handler($protocol) {
        switch ($protocol) {
            case 'ftp':
                return new UWPBM_FTP();
            case 'sftp':
                return new UWPBM_SFTP();
            case 'local':
            default:
                return new UWPBM_Local();
        }
    }
    
    /**
     * AJAX: Create schedule
     */
    public function ajax_create_schedule() {
        check_ajax_referer('uwpbm_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $scheduler = new UWPBM_Scheduler();
        $schedule_id = $scheduler->add_schedule($_POST);
        
        wp_send_json_success(['schedule_id' => $schedule_id]);
    }
    
    /**
     * AJAX: Delete schedule
     */
    public function ajax_delete_schedule() {
        check_ajax_referer('uwpbm_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $schedule_id = sanitize_text_field($_POST['schedule_id']);
        $scheduler = new UWPBM_Scheduler();
        $scheduler->delete_schedule($schedule_id);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Toggle monitoring
     */
    public function ajax_toggle_monitor() {
        check_ajax_referer('uwpbm_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $enabled = !empty($_POST['enabled']);
        $monitor = new UWPBM_Monitor();
        
        if ($enabled) {
            $monitor->enable_monitoring();
        } else {
            $monitor->disable_monitoring();
        }
        
        wp_send_json_success(['enabled' => $enabled]);
    }
    
    /**
     * Get backup progress
     *
     * @param int $backup_id Backup ID
     * @return array
     */
    private function get_backup_progress($backup_id) {
        global $wpdb;
        
        $backup = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups WHERE id = %d",
            $backup_id
        ));
        
        if (!$backup) {
            return ['error' => __('Backup not found', 'ultimate-wp-backup-migration')];
        }
        
        return [
            'status' => $backup->status,
            'progress' => 50, // Placeholder
            'message' => __('Processing...', 'ultimate-wp-backup-migration'),
        ];
    }
}