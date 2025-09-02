<?php
/**
 * Core migration engine
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main migration engine class
 */
class UWPBM_Migrator {
    
    /**
     * Available protocols
     *
     * @var array
     */
    private $protocols = [];
    
    /**
     * Current operation ID
     *
     * @var int
     */
    private $operation_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_protocols();
    }
    
    /**
     * Start export process
     *
     * @param array $options Export options
     * @return int Backup ID
     * @throws Exception
     */
    public function start_export($options) {
        // Validate options
        $options = $this->validate_export_options($options);
        
        // Create backup record
        $backup_id = $this->create_backup_record($options);
        $this->operation_id = $backup_id;
        
        try {
            // Initialize export
            $exporter = new UWPBM_Exporter($backup_id, $options);
            
            // Start export process
            $exporter->run();
            
            return $backup_id;
            
        } catch (Exception $e) {
            $this->update_backup_status($backup_id, 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Start import process
     *
     * @param array $options Import options
     * @return int Import ID
     * @throws Exception
     */
    public function start_import($options) {
        // Validate options
        $options = $this->validate_import_options($options);
        
        // Create import record
        $import_id = $this->create_import_record($options);
        $this->operation_id = $import_id;
        
        try {
            // Initialize importer
            $importer = new UWPBM_Importer($import_id, $options);
            
            // Start import process
            $importer->run();
            
            return $import_id;
            
        } catch (Exception $e) {
            $this->update_import_status($import_id, 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get export progress
     *
     * @param int $backup_id Backup ID
     * @return array Progress data
     */
    public function get_export_progress($backup_id) {
        global $wpdb;
        
        $backup = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups WHERE id = %d",
            $backup_id
        ));
        
        if (!$backup) {
            return ['error' => 'Backup not found'];
        }
        
        // Get progress from transient
        $progress_data = get_transient('uwpbm_export_progress_' . $backup_id);
        
        if (!$progress_data) {
            $progress_data = [
                'progress' => 0,
                'step' => 'initializing',
                'message' => 'Initializing backup...',
            ];
        }
        
        return array_merge($progress_data, [
            'status' => $backup->status,
            'backup_id' => $backup_id,
        ]);
    }
    
    /**
     * Get import progress
     *
     * @param int $import_id Import ID
     * @return array Progress data
     */
    public function get_import_progress($import_id) {
        // Get progress from transient
        $progress_data = get_transient('uwpbm_import_progress_' . $import_id);
        
        if (!$progress_data) {
            $progress_data = [
                'progress' => 0,
                'step' => 'initializing',
                'message' => 'Initializing import...',
            ];
        }
        
        return array_merge($progress_data, [
            'import_id' => $import_id,
        ]);
    }
    
    /**
     * Update export progress
     *
     * @param int $backup_id Backup ID
     * @param int $progress Progress percentage
     * @param string $step Current step
     * @param string $message Progress message
     */
    public function update_export_progress($backup_id, $progress, $step, $message) {
        $progress_data = [
            'progress' => min(100, max(0, intval($progress))),
            'step' => sanitize_text_field($step),
            'message' => sanitize_text_field($message),
            'updated' => time(),
        ];
        
        set_transient('uwpbm_export_progress_' . $backup_id, $progress_data, HOUR_IN_SECONDS);
        
        // Log progress
        $this->log('Export progress: ' . $progress . '% - ' . $message, $backup_id);
    }
    
    /**
     * Update import progress
     *
     * @param int $import_id Import ID
     * @param int $progress Progress percentage
     * @param string $step Current step
     * @param string $message Progress message
     */
    public function update_import_progress($import_id, $progress, $step, $message) {
        $progress_data = [
            'progress' => min(100, max(0, intval($progress))),
            'step' => sanitize_text_field($step),
            'message' => sanitize_text_field($message),
            'updated' => time(),
        ];
        
        set_transient('uwpbm_import_progress_' . $import_id, $progress_data, HOUR_IN_SECONDS);
        
        // Log progress
        $this->log('Import progress: ' . $progress . '% - ' . $message, $import_id);
    }
    
    /**
     * Load available protocols
     */
    private function load_protocols() {
        $this->protocols = [
            'local' => 'UWPBM_Local',
            'ftp' => 'UWPBM_FTP',
            'sftp' => 'UWPBM_SFTP',
        ];
        
        // Allow plugins to add custom protocols
        $this->protocols = apply_filters('uwpbm_protocols', $this->protocols);
    }
    
    /**
     * Get protocol handler
     *
     * @param string $protocol Protocol name
     * @return object Protocol handler
     * @throws Exception
     */
    public function get_protocol_handler($protocol) {
        if (!isset($this->protocols[$protocol])) {
            throw new Exception('Unsupported protocol: ' . $protocol);
        }
        
        $class_name = $this->protocols[$protocol];
        
        if (!class_exists($class_name)) {
            throw new Exception('Protocol class not found: ' . $class_name);
        }
        
        return new $class_name();
    }
    
    /**
     * Validate export options
     *
     * @param array $options Export options
     * @return array Validated options
     * @throws Exception
     */
    private function validate_export_options($options) {
        $defaults = [
            'name' => 'backup-' . date('Y-m-d-H-i-s'),
            'protocol' => 'local',
            'include_database' => true,
            'include_media' => true,
            'include_plugins' => true,
            'include_themes' => true,
            'include_uploads' => true,
            'compression' => 'zip',
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Validate protocol
        if (!isset($this->protocols[$options['protocol']])) {
            throw new Exception('Invalid protocol: ' . $options['protocol']);
        }
        
        // Sanitize name
        $options['name'] = sanitize_file_name($options['name']);
        if (empty($options['name'])) {
            $options['name'] = $defaults['name'];
        }
        
        return $options;
    }
    
    /**
     * Validate import options
     *
     * @param array $options Import options
     * @return array Validated options
     * @throws Exception
     */
    private function validate_import_options($options) {
        $defaults = [
            'backup_id' => 0,
            'source_path' => '',
            'protocol' => 'local',
            'overwrite_database' => true,
            'overwrite_files' => true,
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Validate backup ID or source path
        if (empty($options['backup_id']) && empty($options['source_path'])) {
            throw new Exception('Either backup_id or source_path must be specified');
        }
        
        return $options;
    }
    
    /**
     * Create backup record
     *
     * @param array $options Export options
     * @return int Backup ID
     */
    private function create_backup_record($options) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'uwpbm_backups',
            [
                'name' => $options['name'],
                'type' => 'manual',
                'status' => 'running',
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create import record
     *
     * @param array $options Import options
     * @return int Import ID
     */
    private function create_import_record($options) {
        global $wpdb;
        
        // Create imports table if it doesn't exist
        $this->create_imports_table();
        
        $wpdb->insert(
            $wpdb->prefix . 'uwpbm_imports',
            [
                'backup_id' => $options['backup_id'],
                'source_path' => $options['source_path'],
                'status' => 'running',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update backup status
     *
     * @param int $backup_id Backup ID
     * @param string $status New status
     * @param string $message Optional message
     */
    public function update_backup_status($backup_id, $status, $message = '') {
        global $wpdb;
        
        $update_data = [
            'status' => $status,
        ];
        
        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }
        
        $wpdb->update(
            $wpdb->prefix . 'uwpbm_backups',
            $update_data,
            ['id' => $backup_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );
        
        if ($message) {
            $this->log($message, $backup_id);
        }
        
        // Clean up progress transient
        delete_transient('uwpbm_export_progress_' . $backup_id);
    }
    
    /**
     * Update import status
     *
     * @param int $import_id Import ID
     * @param string $status New status
     * @param string $message Optional message
     */
    public function update_import_status($import_id, $status, $message = '') {
        global $wpdb;
        
        $update_data = [
            'status' => $status,
        ];
        
        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }
        
        $wpdb->update(
            $wpdb->prefix . 'uwpbm_imports',
            $update_data,
            ['id' => $import_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );
        
        if ($message) {
            $this->log($message, $import_id);
        }
        
        // Clean up progress transient
        delete_transient('uwpbm_import_progress_' . $import_id);
    }
    
    /**
     * Create imports table
     */
    private function create_imports_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uwpbm_imports';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            backup_id bigint(20) DEFAULT 0,
            source_path text,
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Log message
     *
     * @param string $message Log message
     * @param int $operation_id Operation ID
     */
    private function log($message, $operation_id = null) {
        if (!UWPBM_Core::get_option('uwpbm_enable_logging', true)) {
            return;
        }
        
        $log_entry = '[' . date('Y-m-d H:i:s') . '] ';
        
        if ($operation_id) {
            $log_entry .= '[ID:' . $operation_id . '] ';
        }
        
        $log_entry .= $message . PHP_EOL;
        
        // Write to log file
        $log_file = WP_CONTENT_DIR . '/uwpbm-logs/migration.log';
        $log_dir = dirname($log_file);
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}