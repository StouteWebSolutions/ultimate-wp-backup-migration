<?php
/**
 * Import pipeline class
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import pipeline handler
 */
class UWPBM_Importer {
    
    private $import_id;
    private $options;
    private $migrator;
    private $temp_dir;
    
    public function __construct($import_id, $options) {
        $this->import_id = $import_id;
        $this->options = $options;
        $this->migrator = new UWPBM_Migrator();
        $this->setup_paths();
    }
    
    public function run() {
        try {
            $this->update_progress(0, 'init', 'Initializing import...');
            
            $steps = [
                'download' => 20,
                'extract' => 40,
                'database' => 70,
                'files' => 90,
                'cleanup' => 100,
            ];
            
            foreach ($steps as $step => $progress) {
                $method = 'import_' . $step;
                if (method_exists($this, $method)) {
                    $this->$method();
                    $this->update_progress($progress, $step, 'Completed ' . $step);
                }
            }
            
            $this->migrator->update_import_status($this->import_id, 'completed');
            
        } catch (Exception $e) {
            $this->migrator->update_import_status($this->import_id, 'failed', $e->getMessage());
            $this->cleanup_temp_directory();
            throw $e;
        }
    }
    
    private function import_download() {
        if (!empty($this->options['backup_id'])) {
            $this->download_from_backup();
        } else {
            $this->download_from_source();
        }
    }
    
    private function download_from_backup() {
        global $wpdb;
        
        $backup = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups WHERE id = %d",
            $this->options['backup_id']
        ));
        
        if (!$backup || !$backup->location) {
            throw new Exception('Backup not found or has no location data');
        }
        
        $location = json_decode($backup->location, true);
        
        if ($location['protocol'] === 'local' && file_exists($location['local_path'])) {
            $this->archive_path = $location['local_path'];
        } else {
            $protocol = $this->migrator->get_protocol_handler($location['protocol']);
            $settings = get_option('uwpbm_' . $location['protocol'] . '_settings', []);
            
            $this->archive_path = $this->temp_dir . '/backup.zip';
            $protocol->download($location['path'], $this->archive_path, $settings);
        }
    }
    
    private function download_from_source() {
        // Copy from source path
        if (!file_exists($this->options['source_path'])) {
            throw new Exception('Source file not found');
        }
        
        $this->archive_path = $this->options['source_path'];
    }
    
    private function import_extract() {
        $this->update_progress(25, 'extract', 'Extracting archive...');
        
        $archiver = new UWPBM_Archiver();
        $extract_dir = $this->temp_dir . '/extracted';
        
        $archiver->extract_archive($this->archive_path, $extract_dir);
        $this->extract_dir = $extract_dir;
    }
    
    private function import_database() {
        if (!$this->options['overwrite_database']) {
            return;
        }
        
        $this->update_progress(45, 'database', 'Importing database...');
        
        $sql_file = $this->extract_dir . '/database.sql';
        if (!file_exists($sql_file)) {
            throw new Exception('Database file not found in backup');
        }
        
        global $wpdb;
        $sql = file_get_contents($sql_file);
        
        // Split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (empty($query) || strpos($query, '--') === 0) {
                continue;
            }
            
            $wpdb->query($query);
            
            if ($wpdb->last_error) {
                throw new Exception('Database import error: ' . $wpdb->last_error);
            }
        }
    }
    
    private function import_files() {
        if (!$this->options['overwrite_files']) {
            return;
        }
        
        $this->update_progress(75, 'files', 'Importing files...');
        
        // Import wp-content
        $source_content = $this->extract_dir . '/wp-content';
        if (is_dir($source_content)) {
            $this->copy_directory($source_content, WP_CONTENT_DIR);
        }
        
        // Import wp-config.php
        $wp_config = $this->extract_dir . '/wp-config.php';
        if (file_exists($wp_config)) {
            copy($wp_config, ABSPATH . 'wp-config.php');
        }
        
        // Import .htaccess
        $htaccess = $this->extract_dir . '/.htaccess';
        if (file_exists($htaccess)) {
            copy($htaccess, ABSPATH . '.htaccess');
        }
    }
    
    private function import_cleanup() {
        $this->cleanup_temp_directory();
    }
    
    private function copy_directory($source, $destination) {
        if (!is_dir($destination)) {
            wp_mkdir_p($destination);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $dest_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                wp_mkdir_p($dest_path);
            } else {
                copy($item, $dest_path);
            }
        }
    }
    
    private function setup_paths() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/uwpbm/temp-import-' . $this->import_id;
        wp_mkdir_p($this->temp_dir);
    }
    
    private function cleanup_temp_directory() {
        if (is_dir($this->temp_dir)) {
            $this->delete_directory($this->temp_dir);
        }
    }
    
    private function delete_directory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    private function update_progress($progress, $step, $message) {
        $this->migrator->update_import_progress($this->import_id, $progress, $step, $message);
    }
}