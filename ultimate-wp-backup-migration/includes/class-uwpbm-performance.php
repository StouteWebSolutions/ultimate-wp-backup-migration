<?php
/**
 * Performance optimizations
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_Performance {
    
    public static function optimize_for_large_sites() {
        // Increase limits for backup operations
        if (function_exists('ini_set')) {
            ini_set('max_execution_time', UWPBM_Core::get_option('uwpbm_max_execution_time', 300));
            ini_set('memory_limit', UWPBM_Core::get_option('uwpbm_memory_limit', '512M'));
        }
        
        // Disable WordPress maintenance mode during backups
        if (!defined('WP_INSTALLING')) {
            define('WP_INSTALLING', true);
        }
    }
    
    public static function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/uwpbm';
        
        if (!is_dir($temp_dir)) {
            return;
        }
        
        $files = glob($temp_dir . '/temp-*');
        foreach ($files as $file) {
            if (is_dir($file) && filemtime($file) < (time() - 3600)) { // 1 hour old
                self::delete_directory($file);
            }
        }
    }
    
    public static function cleanup_old_backups() {
        global $wpdb;
        
        $retention_days = UWPBM_Core::get_option('uwpbm_backup_retention', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $old_backups = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups WHERE created_at < %s",
            $cutoff_date
        ));
        
        foreach ($old_backups as $backup) {
            if ($backup->location) {
                $location = json_decode($backup->location, true);
                if (isset($location['local_path']) && file_exists($location['local_path'])) {
                    unlink($location['local_path']);
                }
            }
            
            $wpdb->delete(
                $wpdb->prefix . 'uwpbm_backups',
                ['id' => $backup->id],
                ['%d']
            );
        }
    }
    
    private static function delete_directory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::delete_directory($path) : unlink($path);
        }
        rmdir($dir);
    }
}