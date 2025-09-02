<?php
/**
 * Incremental backup system
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_Incremental {
    
    private $last_backup_time;
    private $changed_files = [];
    private $changed_tables = [];
    
    public function __construct() {
        $this->last_backup_time = get_option('uwpbm_last_backup_time', 0);
    }
    
    public function get_changed_files() {
        $this->scan_directory(ABSPATH, '');
        return $this->changed_files;
    }
    
    public function get_changed_tables() {
        global $wpdb;
        
        $tables = $wpdb->get_col("SHOW TABLES");
        $last_backup = get_option('uwpbm_last_table_checksums', []);
        
        foreach ($tables as $table) {
            $checksum = $this->get_table_checksum($table);
            
            if (!isset($last_backup[$table]) || $last_backup[$table] !== $checksum) {
                $this->changed_tables[] = $table;
            }
        }
        
        return $this->changed_tables;
    }
    
    public function create_incremental_backup($options) {
        $changed_files = $this->get_changed_files();
        $changed_tables = $this->get_changed_tables();
        
        if (empty($changed_files) && empty($changed_tables)) {
            throw new Exception('No changes detected since last backup');
        }
        
        $options['incremental'] = true;
        $options['changed_files'] = $changed_files;
        $options['changed_tables'] = $changed_tables;
        
        $migrator = new UWPBM_Migrator();
        return $migrator->start_export($options);
    }
    
    public function update_baseline() {
        update_option('uwpbm_last_backup_time', time());
        
        // Update table checksums
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $checksums = [];
        
        foreach ($tables as $table) {
            $checksums[$table] = $this->get_table_checksum($table);
        }
        
        update_option('uwpbm_last_table_checksums', $checksums);
    }
    
    private function scan_directory($dir, $relative_path) {
        if (!is_dir($dir)) return;
        
        $skip_dirs = ['wp-content/cache', 'wp-content/uploads/uwpbm'];
        
        $iterator = new DirectoryIterator($dir);
        foreach ($iterator as $file) {
            if ($file->isDot()) continue;
            
            $file_path = $dir . '/' . $file->getFilename();
            $rel_path = $relative_path . '/' . $file->getFilename();
            
            if ($file->isDir()) {
                if (!$this->should_skip_directory($rel_path, $skip_dirs)) {
                    $this->scan_directory($file_path, $rel_path);
                }
            } else {
                if ($file->getMTime() > $this->last_backup_time) {
                    $this->changed_files[] = $rel_path;
                }
            }
        }
    }
    
    private function should_skip_directory($path, $skip_dirs) {
        foreach ($skip_dirs as $skip) {
            if (strpos($path, $skip) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function get_table_checksum($table) {
        global $wpdb;
        
        $result = $wpdb->get_var("CHECKSUM TABLE `$table`");
        return $result ?: md5($table . time());
    }
}