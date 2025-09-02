<?php
/**
 * Local file system protocol
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Local file system protocol handler
 */
class UWPBM_Local implements UWPBM_Protocol_Interface {
    
    /**
     * Test connection to local storage
     *
     * @param array $settings Connection settings
     * @return array Connection test results
     * @throws Exception
     */
    public function test_connection($settings) {
        $storage_path = $this->get_storage_path($settings);
        
        // Check if directory exists and is writable
        if (!is_dir($storage_path)) {
            if (!wp_mkdir_p($storage_path)) {
                throw new Exception('Cannot create storage directory: ' . $storage_path);
            }
        }
        
        if (!is_writable($storage_path)) {
            throw new Exception('Storage directory is not writable: ' . $storage_path);
        }
        
        // Test write permissions
        $test_file = $storage_path . '/test-' . time() . '.tmp';
        if (!file_put_contents($test_file, 'test')) {
            throw new Exception('Cannot write to storage directory');
        }
        
        // Clean up test file
        unlink($test_file);
        
        return [
            'status' => 'success',
            'storage_path' => $storage_path,
            'free_space' => $this->format_bytes(disk_free_space($storage_path)),
            'total_space' => $this->format_bytes(disk_total_space($storage_path)),
        ];
    }
    
    /**
     * Upload file to local storage
     *
     * @param string $local_file Local file path
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return bool Success status
     * @throws Exception
     */
    public function upload($local_file, $remote_path, $settings) {
        if (!file_exists($local_file)) {
            throw new Exception('Source file not found: ' . $local_file);
        }
        
        $storage_path = $this->get_storage_path($settings);
        $destination = $storage_path . '/' . ltrim($remote_path, '/');
        
        // Create destination directory if needed
        $destination_dir = dirname($destination);
        if (!is_dir($destination_dir)) {
            if (!wp_mkdir_p($destination_dir)) {
                throw new Exception('Cannot create destination directory: ' . $destination_dir);
            }
        }
        
        // Copy file
        if (!copy($local_file, $destination)) {
            throw new Exception('Failed to copy file to local storage');
        }
        
        return true;
    }
    
    /**
     * Download file from local storage
     *
     * @param string $remote_path Remote file path
     * @param string $local_file Local file path
     * @param array $settings Connection settings
     * @return bool Success status
     * @throws Exception
     */
    public function download($remote_path, $local_file, $settings) {
        $storage_path = $this->get_storage_path($settings);
        $source = $storage_path . '/' . ltrim($remote_path, '/');
        
        if (!file_exists($source)) {
            throw new Exception('Source file not found: ' . $source);
        }
        
        // Create local directory if needed
        $local_dir = dirname($local_file);
        if (!is_dir($local_dir)) {
            if (!wp_mkdir_p($local_dir)) {
                throw new Exception('Cannot create local directory: ' . $local_dir);
            }
        }
        
        // Copy file
        if (!copy($source, $local_file)) {
            throw new Exception('Failed to copy file from local storage');
        }
        
        return true;
    }
    
    /**
     * List files in local storage directory
     *
     * @param string $remote_path Remote directory path
     * @param array $settings Connection settings
     * @return array File list
     * @throws Exception
     */
    public function list_files($remote_path, $settings) {
        $storage_path = $this->get_storage_path($settings);
        $directory = $storage_path . '/' . ltrim($remote_path, '/');
        
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        $iterator = new DirectoryIterator($directory);
        
        foreach ($iterator as $file) {
            if ($file->isDot()) {
                continue;
            }
            
            $files[] = [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'type' => $file->isDir() ? 'directory' : 'file',
                'modified' => $file->getMTime(),
            ];
        }
        
        return $files;
    }
    
    /**
     * Delete file from local storage
     *
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return bool Success status
     * @throws Exception
     */
    public function delete($remote_path, $settings) {
        $storage_path = $this->get_storage_path($settings);
        $file_path = $storage_path . '/' . ltrim($remote_path, '/');
        
        if (!file_exists($file_path)) {
            return true; // File doesn't exist, consider it deleted
        }
        
        if (!unlink($file_path)) {
            throw new Exception('Failed to delete file: ' . $file_path);
        }
        
        return true;
    }
    
    /**
     * Check if file exists in local storage
     *
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return bool File exists
     * @throws Exception
     */
    public function file_exists($remote_path, $settings) {
        $storage_path = $this->get_storage_path($settings);
        $file_path = $storage_path . '/' . ltrim($remote_path, '/');
        
        return file_exists($file_path);
    }
    
    /**
     * Get file size from local storage
     *
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return int File size in bytes
     * @throws Exception
     */
    public function get_file_size($remote_path, $settings) {
        $storage_path = $this->get_storage_path($settings);
        $file_path = $storage_path . '/' . ltrim($remote_path, '/');
        
        if (!file_exists($file_path)) {
            throw new Exception('File not found: ' . $file_path);
        }
        
        return filesize($file_path);
    }
    
    /**
     * Get storage path
     *
     * @param array $settings Connection settings
     * @return string Storage path
     */
    private function get_storage_path($settings) {
        if (!empty($settings['storage_path'])) {
            return rtrim($settings['storage_path'], '/');
        }
        
        // Default to uploads directory
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/uwpbm-backups';
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private function format_bytes($bytes) {
        if ($bytes === false) {
            return 'Unknown';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}