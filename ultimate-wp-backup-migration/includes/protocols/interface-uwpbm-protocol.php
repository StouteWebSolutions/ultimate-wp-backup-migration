<?php
/**
 * Storage protocol interface
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Storage protocol interface
 */
interface UWPBM_Protocol_Interface {
    
    /**
     * Test connection to storage provider
     *
     * @param array $settings Connection settings
     * @return array Connection test results
     * @throws Exception
     */
    public function test_connection($settings);
    
    /**
     * Upload file to storage
     *
     * @param string $local_file Local file path
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return bool Success status
     * @throws Exception
     */
    public function upload($local_file, $remote_path, $settings);
    
    /**
     * Download file from storage
     *
     * @param string $remote_path Remote file path
     * @param string $local_file Local file path
     * @param array $settings Connection settings
     * @return bool Success status
     * @throws Exception
     */
    public function download($remote_path, $local_file, $settings);
    
    /**
     * List files in remote directory
     *
     * @param string $remote_path Remote directory path
     * @param array $settings Connection settings
     * @return array File list
     * @throws Exception
     */
    public function list_files($remote_path, $settings);
    
    /**
     * Delete file from storage
     *
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return bool Success status
     * @throws Exception
     */
    public function delete($remote_path, $settings);
    
    /**
     * Check if file exists in storage
     *
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return bool File exists
     * @throws Exception
     */
    public function file_exists($remote_path, $settings);
    
    /**
     * Get file size from storage
     *
     * @param string $remote_path Remote file path
     * @param array $settings Connection settings
     * @return int File size in bytes
     * @throws Exception
     */
    public function get_file_size($remote_path, $settings);
}