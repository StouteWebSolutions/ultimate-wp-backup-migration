<?php
/**
 * Export pipeline class
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export pipeline handler
 */
class UWPBM_Exporter {
    
    /**
     * Backup ID
     *
     * @var int
     */
    private $backup_id;
    
    /**
     * Export options
     *
     * @var array
     */
    private $options;
    
    /**
     * Migrator instance
     *
     * @var UWPBM_Migrator
     */
    private $migrator;
    
    /**
     * Archive path
     *
     * @var string
     */
    private $archive_path;
    
    /**
     * Temporary directory
     *
     * @var string
     */
    private $temp_dir;
    
    /**
     * Constructor
     *
     * @param int $backup_id Backup ID
     * @param array $options Export options
     */
    public function __construct($backup_id, $options) {
        $this->backup_id = $backup_id;
        $this->options = $options;
        $this->migrator = new UWPBM_Migrator();
        
        $this->setup_paths();
    }
    
    /**
     * Run export process
     *
     * @throws Exception
     */
    public function run() {
        try {
            // Optimize for large sites
            UWPBM_Performance::optimize_for_large_sites();
            
            $this->update_progress(0, 'init', 'Initializing export...');
            
            // Create temporary directory
            $this->create_temp_directory();
            
            // Export steps
            $steps = [
                'database' => 20,
                'content' => 40,
                'media' => 60,
                'plugins' => 70,
                'themes' => 80,
                'archive' => 90,
                'upload' => 100,
            ];
            
            foreach ($steps as $step => $progress) {
                $method = 'export_' . $step;
                if (method_exists($this, $method)) {
                    $this->$method();
                    $this->update_progress($progress, $step, 'Completed ' . $step);
                }
            }
            
            // Mark as completed
            $this->migrator->update_backup_status($this->backup_id, 'completed');
            
            // Clean up temporary files
            $this->cleanup_temp_directory();
            
        } catch (Exception $e) {
            $this->migrator->update_backup_status($this->backup_id, 'failed', $e->getMessage());
            $this->cleanup_temp_directory();
            throw $e;
        }
    }
    
    /**
     * Export database
     *
     * @throws Exception
     */
    private function export_database() {
        if (!$this->options['include_database']) {
            return;
        }
        
        $this->update_progress(10, 'database', 'Exporting database...');
        
        global $wpdb;
        
        $sql_file = $this->temp_dir . '/database.sql';
        $handle = fopen($sql_file, 'w');
        
        if (!$handle) {
            throw new Exception('Cannot create database export file');
        }
        
        // Write header
        fwrite($handle, "-- Ultimate WordPress Backup Migration Database Export\n");
        fwrite($handle, "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
        
        // Get all tables
        $tables = $wpdb->get_col("SHOW TABLES");
        
        foreach ($tables as $table) {
            $this->export_table($handle, $table);
        }
        
        fclose($handle);
        
        $this->update_progress(20, 'database', 'Database export completed');
    }
    
    /**
     * Export single table
     *
     * @param resource $handle File handle
     * @param string $table Table name
     */
    private function export_table($handle, $table) {
        global $wpdb;
        
        // Table structure
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
        fwrite($handle, "\n-- Table structure for table `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $create_table[1] . ";\n\n");
        
        // Table data
        $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
        
        if (!empty($rows)) {
            fwrite($handle, "-- Dumping data for table `$table`\n");
            fwrite($handle, "INSERT INTO `$table` VALUES\n");
            
            $first = true;
            foreach ($rows as $row) {
                if (!$first) {
                    fwrite($handle, ",\n");
                }
                
                $values = array_map(function($value) use ($wpdb) {
                    return $value === null ? 'NULL' : "'" . $wpdb->_escape($value) . "'";
                }, array_values($row));
                
                fwrite($handle, '(' . implode(', ', $values) . ')');
                $first = false;
            }
            
            fwrite($handle, ";\n\n");
        }
    }
    
    /**
     * Export content files
     *
     * @throws Exception
     */
    private function export_content() {
        $this->update_progress(25, 'content', 'Exporting content files...');
        
        $content_dir = $this->temp_dir . '/wp-content';
        wp_mkdir_p($content_dir);
        
        // Copy wp-config.php
        if (file_exists(ABSPATH . 'wp-config.php')) {
            copy(ABSPATH . 'wp-config.php', $this->temp_dir . '/wp-config.php');
        }
        
        // Copy .htaccess
        if (file_exists(ABSPATH . '.htaccess')) {
            copy(ABSPATH . '.htaccess', $this->temp_dir . '/.htaccess');
        }
        
        $this->update_progress(40, 'content', 'Content files exported');
    }
    
    /**
     * Export media files
     *
     * @throws Exception
     */
    private function export_media() {
        if (!$this->options['include_media']) {
            return;
        }
        
        $this->update_progress(45, 'media', 'Exporting media files...');
        
        $uploads_dir = wp_upload_dir();
        $source = $uploads_dir['basedir'];
        $destination = $this->temp_dir . '/wp-content/uploads';
        
        if (is_dir($source)) {
            $this->copy_directory($source, $destination);
        }
        
        $this->update_progress(60, 'media', 'Media files exported');
    }
    
    /**
     * Export plugins
     *
     * @throws Exception
     */
    private function export_plugins() {
        if (!$this->options['include_plugins']) {
            return;
        }
        
        $this->update_progress(65, 'plugins', 'Exporting plugins...');
        
        $source = WP_PLUGIN_DIR;
        $destination = $this->temp_dir . '/wp-content/plugins';
        
        if (is_dir($source)) {
            $this->copy_directory($source, $destination);
        }
        
        $this->update_progress(70, 'plugins', 'Plugins exported');
    }
    
    /**
     * Export themes
     *
     * @throws Exception
     */
    private function export_themes() {
        if (!$this->options['include_themes']) {
            return;
        }
        
        $this->update_progress(75, 'themes', 'Exporting themes...');
        
        $source = get_theme_root();
        $destination = $this->temp_dir . '/wp-content/themes';
        
        if (is_dir($source)) {
            $this->copy_directory($source, $destination);
        }
        
        $this->update_progress(80, 'themes', 'Themes exported');
    }
    
    /**
     * Create archive
     *
     * @throws Exception
     */
    private function export_archive() {
        $this->update_progress(85, 'archive', 'Creating archive...');
        
        $archiver = new UWPBM_Archiver();
        $archiver->create_archive($this->temp_dir, $this->archive_path);
        
        // Update backup size
        $this->update_backup_size();
        
        $this->update_progress(90, 'archive', 'Archive created');
    }
    
    /**
     * Upload to destination
     *
     * @throws Exception
     */
    private function export_upload() {
        $this->update_progress(95, 'upload', 'Uploading to destination...');
        
        $protocol = $this->migrator->get_protocol_handler($this->options['protocol']);
        
        // Get protocol settings
        $settings = get_option('uwpbm_' . $this->options['protocol'] . '_settings', []);
        
        if (empty($settings) && $this->options['protocol'] !== 'local') {
            throw new Exception('No settings found for protocol: ' . $this->options['protocol']);
        }
        
        // Upload archive
        $remote_path = $this->options['name'] . '.zip';
        $protocol->upload($this->archive_path, $remote_path, $settings);
        
        // Update backup location
        $this->update_backup_location($remote_path);
        
        $this->update_progress(100, 'upload', 'Upload completed');
    }
    
    /**
     * Copy directory recursively
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     */
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
    
    /**
     * Setup file paths
     */
    private function setup_paths() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/uwpbm';
        
        if (!is_dir($base_dir)) {
            wp_mkdir_p($base_dir);
        }
        
        $this->temp_dir = $base_dir . '/temp-' . $this->backup_id;
        $this->archive_path = $base_dir . '/' . $this->options['name'] . '.zip';
    }
    
    /**
     * Create temporary directory
     *
     * @throws Exception
     */
    private function create_temp_directory() {
        if (!wp_mkdir_p($this->temp_dir)) {
            throw new Exception('Cannot create temporary directory: ' . $this->temp_dir);
        }
    }
    
    /**
     * Clean up temporary directory
     */
    private function cleanup_temp_directory() {
        if (is_dir($this->temp_dir)) {
            $this->delete_directory($this->temp_dir);
        }
    }
    
    /**
     * Delete directory recursively
     *
     * @param string $dir Directory path
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Update backup size
     */
    private function update_backup_size() {
        if (file_exists($this->archive_path)) {
            $size = filesize($this->archive_path);
            
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'uwpbm_backups',
                ['size' => $size],
                ['id' => $this->backup_id],
                ['%d'],
                ['%d']
            );
        }
    }
    
    /**
     * Update backup location
     *
     * @param string $location Backup location
     */
    private function update_backup_location($location) {
        $location_data = [
            'protocol' => $this->options['protocol'],
            'path' => $location,
            'local_path' => $this->archive_path,
        ];
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'uwpbm_backups',
            ['location' => json_encode($location_data)],
            ['id' => $this->backup_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Update progress
     *
     * @param int $progress Progress percentage
     * @param string $step Current step
     * @param string $message Progress message
     */
    private function update_progress($progress, $step, $message) {
        $this->migrator->update_export_progress($this->backup_id, $progress, $step, $message);
    }
}