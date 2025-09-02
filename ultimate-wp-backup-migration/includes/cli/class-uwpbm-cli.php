<?php
/**
 * WP-CLI commands for Ultimate WordPress Backup Migration
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP-CLI command handler
 */
class UWPBM_CLI {
    
    /**
     * Constructor
     */
    public function __construct() {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('uwpbm', $this);
        }
    }
    
    /**
     * Create a backup
     *
     * ## OPTIONS
     *
     * [--protocol=<protocol>]
     * : Storage protocol (local, ftp, sftp)
     * ---
     * default: local
     * options:
     *   - local
     *   - ftp
     *   - sftp
     * ---
     *
     * [--name=<name>]
     * : Backup name
     *
     * [--exclude-database]
     * : Exclude database from backup
     *
     * [--exclude-media]
     * : Exclude media files from backup
     *
     * [--exclude-plugins]
     * : Exclude plugins from backup
     *
     * [--exclude-themes]
     * : Exclude themes from backup
     *
     * ## EXAMPLES
     *
     *     wp uwpbm backup --protocol=local --name="manual-backup"
     *     wp uwpbm backup --protocol=ftp --exclude-media
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function backup($args, $assoc_args) {
        $options = wp_parse_args($assoc_args, [
            'protocol' => 'local',
            'name' => 'cli-backup-' . date('Y-m-d-H-i-s'),
            'exclude-database' => false,
            'exclude-media' => false,
            'exclude-plugins' => false,
            'exclude-themes' => false,
        ]);
        
        WP_CLI::line('Starting backup...');
        WP_CLI::line('Protocol: ' . $options['protocol']);
        WP_CLI::line('Name: ' . $options['name']);
        
        try {
            $migrator = new UWPBM_Migrator();
            $backup_id = $migrator->start_export($options);
            
            WP_CLI::success('Backup started with ID: ' . $backup_id);
            
            // Monitor progress
            $this->monitor_progress($backup_id, 'backup');
            
        } catch (Exception $e) {
            WP_CLI::error('Backup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore from a backup
     *
     * ## OPTIONS
     *
     * <backup-id>
     * : Backup ID to restore
     *
     * [--confirm]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp uwpbm restore 123
     *     wp uwpbm restore 123 --confirm
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function restore($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Please specify a backup ID to restore.');
        }
        
        $backup_id = intval($args[0]);
        $confirm = isset($assoc_args['confirm']);
        
        // Get backup info
        global $wpdb;
        $backup = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups WHERE id = %d",
            $backup_id
        ));
        
        if (!$backup) {
            WP_CLI::error('Backup not found.');
        }
        
        if ($backup->status !== 'completed') {
            WP_CLI::error('Backup is not completed and cannot be restored.');
        }
        
        // Confirmation
        if (!$confirm) {
            WP_CLI::confirm('This will overwrite your current site. Are you sure?');
        }
        
        WP_CLI::line('Starting restore...');
        WP_CLI::line('Backup: ' . $backup->name);
        WP_CLI::line('Created: ' . $backup->created_at);
        
        try {
            $migrator = new UWPBM_Migrator();
            $restore_id = $migrator->start_import(['backup_id' => $backup_id]);
            
            WP_CLI::success('Restore started with ID: ' . $restore_id);
            
            // Monitor progress
            $this->monitor_progress($restore_id, 'restore');
            
        } catch (Exception $e) {
            WP_CLI::error('Restore failed: ' . $e->getMessage());
        }
    }
    
    /**
     * List all backups
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     * ---
     *
     * [--status=<status>]
     * : Filter by status
     * ---
     * options:
     *   - completed
     *   - running
     *   - failed
     * ---
     *
     * ## EXAMPLES
     *
     *     wp uwpbm list
     *     wp uwpbm list --format=json
     *     wp uwpbm list --status=completed
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function list($args, $assoc_args) {
        global $wpdb;
        
        $format = $assoc_args['format'] ?? 'table';
        $status = $assoc_args['status'] ?? '';
        
        $sql = "SELECT id, name, type, status, size, created_at, completed_at 
                FROM {$wpdb->prefix}uwpbm_backups";
        
        if ($status) {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $backups = $wpdb->get_results($sql, ARRAY_A);
        
        if (empty($backups)) {
            WP_CLI::line('No backups found.');
            return;
        }
        
        // Format data
        foreach ($backups as &$backup) {
            $backup['size'] = $backup['size'] ? size_format($backup['size']) : '—';
            $backup['created_at'] = date('Y-m-d H:i:s', strtotime($backup['created_at']));
            $backup['completed_at'] = $backup['completed_at'] ? 
                date('Y-m-d H:i:s', strtotime($backup['completed_at'])) : '—';
        }
        
        WP_CLI\Utils\format_items($format, $backups, [
            'id', 'name', 'type', 'status', 'size', 'created_at', 'completed_at'
        ]);
    }
    
    /**
     * Delete a backup
     *
     * ## OPTIONS
     *
     * <backup-id>
     * : Backup ID to delete
     *
     * [--confirm]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp uwpbm delete 123
     *     wp uwpbm delete 123 --confirm
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function delete($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Please specify a backup ID to delete.');
        }
        
        $backup_id = intval($args[0]);
        $confirm = isset($assoc_args['confirm']);
        
        // Get backup info
        global $wpdb;
        $backup = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}uwpbm_backups WHERE id = %d",
            $backup_id
        ));
        
        if (!$backup) {
            WP_CLI::error('Backup not found.');
        }
        
        // Confirmation
        if (!$confirm) {
            WP_CLI::confirm('Are you sure you want to delete this backup? This action cannot be undone.');
        }
        
        try {
            // Delete backup files
            if ($backup->location) {
                $location_data = json_decode($backup->location, true);
                if ($location_data && isset($location_data['path'])) {
                    if (file_exists($location_data['path'])) {
                        unlink($location_data['path']);
                    }
                }
            }
            
            // Delete database record
            $wpdb->delete(
                $wpdb->prefix . 'uwpbm_backups',
                ['id' => $backup_id],
                ['%d']
            );
            
            WP_CLI::success('Backup deleted successfully.');
            
        } catch (Exception $e) {
            WP_CLI::error('Failed to delete backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Test connection to storage provider
     *
     * ## OPTIONS
     *
     * <protocol>
     * : Storage protocol to test
     * ---
     * options:
     *   - ftp
     *   - sftp
     * ---
     *
     * ## EXAMPLES
     *
     *     wp uwpbm test-connection ftp
     *     wp uwpbm test-connection sftp
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function test_connection($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Please specify a protocol to test.');
        }
        
        $protocol = $args[0];
        
        WP_CLI::line('Testing ' . strtoupper($protocol) . ' connection...');
        
        try {
            // Get stored settings for the protocol
            $settings = get_option('uwpbm_' . $protocol . '_settings', []);
            
            if (empty($settings)) {
                WP_CLI::error('No settings found for ' . $protocol . '. Please configure the connection first.');
            }
            
            // Test connection
            $protocol_handler = $this->get_protocol_handler($protocol);
            $result = $protocol_handler->test_connection($settings);
            
            WP_CLI::success('Connection successful!');
            
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    WP_CLI::line($key . ': ' . $value);
                }
            }
            
        } catch (Exception $e) {
            WP_CLI::error('Connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Show plugin status and information
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Named arguments
     */
    public function status($args, $assoc_args) {
        global $wpdb;
        
        WP_CLI::line('Ultimate WordPress Backup Migration Status');
        WP_CLI::line('==========================================');
        
        // Plugin info
        WP_CLI::line('Plugin Version: ' . UWPBM_VERSION);
        WP_CLI::line('WordPress Version: ' . get_bloginfo('version'));
        WP_CLI::line('PHP Version: ' . PHP_VERSION);
        WP_CLI::line('');
        
        // System info
        WP_CLI::line('System Information:');
        WP_CLI::line('- Memory Limit: ' . ini_get('memory_limit'));
        WP_CLI::line('- Max Execution Time: ' . ini_get('max_execution_time') . 's');
        WP_CLI::line('- Site Size: ' . size_format(get_dirsize(ABSPATH)));
        WP_CLI::line('');
        
        // Backup statistics
        $total_backups = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}uwpbm_backups");
        $completed_backups = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}uwpbm_backups WHERE status = 'completed'");
        $failed_backups = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}uwpbm_backups WHERE status = 'failed'");
        
        WP_CLI::line('Backup Statistics:');
        WP_CLI::line('- Total Backups: ' . $total_backups);
        WP_CLI::line('- Completed: ' . $completed_backups);
        WP_CLI::line('- Failed: ' . $failed_backups);
        WP_CLI::line('');
        
        // Recent backup
        $recent_backup = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}uwpbm_backups ORDER BY created_at DESC LIMIT 1");
        if ($recent_backup) {
            WP_CLI::line('Last Backup:');
            WP_CLI::line('- Name: ' . $recent_backup->name);
            WP_CLI::line('- Status: ' . $recent_backup->status);
            WP_CLI::line('- Created: ' . $recent_backup->created_at);
            if ($recent_backup->size) {
                WP_CLI::line('- Size: ' . size_format($recent_backup->size));
            }
        } else {
            WP_CLI::line('No backups found.');
        }
    }
    
    /**
     * Monitor operation progress
     *
     * @param int $operation_id Operation ID
     * @param string $type Operation type (backup|restore)
     */
    private function monitor_progress($operation_id, $type) {
        $progress_bar = WP_CLI\Utils\make_progress_bar('Processing', 100);
        
        $last_progress = 0;
        $start_time = time();
        
        while (true) {
            sleep(2); // Check every 2 seconds
            
            // Get current progress
            $current_progress = $this->get_operation_progress($operation_id, $type);
            
            if ($current_progress === false) {
                $progress_bar->finish();
                WP_CLI::error('Lost connection to operation.');
            }
            
            // Update progress bar
            if ($current_progress['progress'] > $last_progress) {
                $progress_bar->tick($current_progress['progress'] - $last_progress);
                $last_progress = $current_progress['progress'];
            }
            
            // Check if completed
            if ($current_progress['status'] === 'completed') {
                $progress_bar->finish();
                $elapsed = time() - $start_time;
                WP_CLI::success(ucfirst($type) . ' completed successfully in ' . $elapsed . ' seconds.');
                break;
            }
            
            // Check if failed
            if ($current_progress['status'] === 'failed') {
                $progress_bar->finish();
                WP_CLI::error(ucfirst($type) . ' failed: ' . $current_progress['message']);
            }
            
            // Timeout after 30 minutes
            if (time() - $start_time > 1800) {
                $progress_bar->finish();
                WP_CLI::error(ucfirst($type) . ' timed out after 30 minutes.');
            }
        }
    }
    
    /**
     * Get operation progress
     *
     * @param int $operation_id Operation ID
     * @param string $type Operation type
     * @return array|false
     */
    private function get_operation_progress($operation_id, $type) {
        global $wpdb;
        
        $table = $type === 'backup' ? 'uwpbm_backups' : 'uwpbm_restores';
        
        $operation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$table} WHERE id = %d",
            $operation_id
        ));
        
        if (!$operation) {
            return false;
        }
        
        return [
            'status' => $operation->status,
            'progress' => 50, // Placeholder - implement actual progress tracking
            'message' => 'Processing...',
        ];
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
}