<?php
/**
 * Real-time monitoring system
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_Monitor {
    
    private $enabled;
    private $last_check;
    
    public function __construct() {
        $this->enabled = get_option('uwpbm_monitor_enabled', false);
        $this->last_check = get_option('uwpbm_monitor_last_check', 0);
        
        if ($this->enabled) {
            add_action('wp_loaded', [$this, 'check_changes']);
            add_action('save_post', [$this, 'trigger_change_check']);
            add_action('wp_update_nav_menu', [$this, 'trigger_change_check']);
        }
    }
    
    public function enable_monitoring() {
        $this->enabled = true;
        update_option('uwpbm_monitor_enabled', true);
        $this->create_baseline();
    }
    
    public function disable_monitoring() {
        $this->enabled = false;
        update_option('uwpbm_monitor_enabled', false);
    }
    
    public function check_changes() {
        if (!$this->should_check()) {
            return;
        }
        
        $changes = $this->detect_changes();
        
        if (!empty($changes)) {
            $this->handle_changes($changes);
        }
        
        update_option('uwpbm_monitor_last_check', time());
    }
    
    public function trigger_change_check() {
        if (!$this->enabled) {
            return;
        }
        
        // Schedule immediate check
        wp_schedule_single_event(time() + 60, 'uwpbm_monitor_check');
    }
    
    private function should_check() {
        $interval = get_option('uwpbm_monitor_interval', 3600); // 1 hour default
        return (time() - $this->last_check) >= $interval;
    }
    
    private function detect_changes() {
        $changes = [];
        
        // Check database changes
        $db_changes = $this->check_database_changes();
        if (!empty($db_changes)) {
            $changes['database'] = $db_changes;
        }
        
        // Check file changes
        $file_changes = $this->check_file_changes();
        if (!empty($file_changes)) {
            $changes['files'] = $file_changes;
        }
        
        return $changes;
    }
    
    private function check_database_changes() {
        global $wpdb;
        
        $current_checksums = [];
        $tables = $wpdb->get_col("SHOW TABLES");
        
        foreach ($tables as $table) {
            $checksum = $wpdb->get_var("CHECKSUM TABLE `$table`");
            $current_checksums[$table] = $checksum;
        }
        
        $baseline = get_option('uwpbm_monitor_db_baseline', []);
        $changes = [];
        
        foreach ($current_checksums as $table => $checksum) {
            if (!isset($baseline[$table]) || $baseline[$table] !== $checksum) {
                $changes[] = $table;
            }
        }
        
        return $changes;
    }
    
    private function check_file_changes() {
        $critical_files = [
            ABSPATH . 'wp-config.php',
            ABSPATH . '.htaccess',
            get_template_directory() . '/functions.php',
        ];
        
        $changes = [];
        $baseline = get_option('uwpbm_monitor_file_baseline', []);
        
        foreach ($critical_files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $current_hash = md5_file($file);
            $file_key = md5($file);
            
            if (!isset($baseline[$file_key]) || $baseline[$file_key] !== $current_hash) {
                $changes[] = $file;
            }
        }
        
        return $changes;
    }
    
    private function handle_changes($changes) {
        $auto_backup = get_option('uwpbm_monitor_auto_backup', false);
        
        if ($auto_backup) {
            $this->trigger_auto_backup($changes);
        }
        
        $this->log_changes($changes);
        $this->send_change_notification($changes);
    }
    
    private function trigger_auto_backup($changes) {
        try {
            $options = [
                'name' => 'auto-' . date('Y-m-d-H-i-s'),
                'protocol' => get_option('uwpbm_monitor_backup_protocol', 'local'),
                'include_database' => !empty($changes['database']),
                'include_media' => false, // Skip media for auto backups
                'include_plugins' => true,
                'include_themes' => true,
            ];
            
            $migrator = new UWPBM_Migrator();
            $backup_id = $migrator->start_export($options);
            
            $this->log_changes(['auto_backup_created' => $backup_id]);
            
        } catch (Exception $e) {
            $this->log_changes(['auto_backup_failed' => $e->getMessage()]);
        }
    }
    
    private function log_changes($changes) {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'changes' => $changes,
        ];
        
        $log = get_option('uwpbm_monitor_log', []);
        array_unshift($log, $log_entry);
        
        // Keep only last 100 entries
        $log = array_slice($log, 0, 100);
        
        update_option('uwpbm_monitor_log', $log);
    }
    
    private function send_change_notification($changes) {
        $notify_email = get_option('uwpbm_monitor_notify_email', false);
        
        if (!$notify_email) {
            return;
        }
        
        $email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $subject = sprintf('[%s] Site Changes Detected', $site_name);
        
        $message = "Changes detected on your WordPress site:\n\n";
        
        if (!empty($changes['database'])) {
            $message .= "Database tables changed:\n";
            foreach ($changes['database'] as $table) {
                $message .= "- $table\n";
            }
            $message .= "\n";
        }
        
        if (!empty($changes['files'])) {
            $message .= "Files changed:\n";
            foreach ($changes['files'] as $file) {
                $message .= "- $file\n";
            }
        }
        
        $message .= "\nTime: " . current_time('mysql');
        
        wp_mail($email, $subject, $message);
    }
    
    private function create_baseline() {
        // Database baseline
        global $wpdb;
        $db_baseline = [];
        $tables = $wpdb->get_col("SHOW TABLES");
        
        foreach ($tables as $table) {
            $checksum = $wpdb->get_var("CHECKSUM TABLE `$table`");
            $db_baseline[$table] = $checksum;
        }
        
        update_option('uwpbm_monitor_db_baseline', $db_baseline);
        
        // File baseline
        $critical_files = [
            ABSPATH . 'wp-config.php',
            ABSPATH . '.htaccess',
            get_template_directory() . '/functions.php',
        ];
        
        $file_baseline = [];
        foreach ($critical_files as $file) {
            if (file_exists($file)) {
                $file_key = md5($file);
                $file_baseline[$file_key] = md5_file($file);
            }
        }
        
        update_option('uwpbm_monitor_file_baseline', $file_baseline);
    }
    
    public function get_monitor_log() {
        return get_option('uwpbm_monitor_log', []);
    }
}