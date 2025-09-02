<?php
/**
 * FTP protocol handler
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_FTP implements UWPBM_Protocol_Interface {
    
    private $connection;
    
    public function test_connection($settings) {
        $conn = $this->connect($settings);
        
        $result = [
            'status' => 'success',
            'server' => $settings['host'],
            'port' => $settings['port'],
            'directory' => $settings['directory'] ?? '/',
        ];
        
        ftp_close($conn);
        return $result;
    }
    
    public function upload($local_file, $remote_path, $settings) {
        if (!file_exists($local_file)) {
            throw new Exception('Local file not found: ' . $local_file);
        }
        
        $conn = $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        $this->create_remote_directory(dirname($remote_file), $conn);
        
        $result = ftp_put($conn, $remote_file, $local_file, FTP_BINARY);
        ftp_close($conn);
        
        if (!$result) {
            throw new Exception('FTP upload failed');
        }
        
        return true;
    }
    
    public function download($remote_path, $local_file, $settings) {
        $conn = $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        
        $local_dir = dirname($local_file);
        if (!is_dir($local_dir)) {
            wp_mkdir_p($local_dir);
        }
        
        $result = ftp_get($conn, $local_file, $remote_file, FTP_BINARY);
        ftp_close($conn);
        
        if (!$result) {
            throw new Exception('FTP download failed');
        }
        
        return true;
    }
    
    public function list_files($remote_path, $settings) {
        $conn = $this->connect($settings);
        
        $remote_dir = $this->get_remote_path($remote_path, $settings);
        $files = ftp_nlist($conn, $remote_dir);
        
        ftp_close($conn);
        
        if ($files === false) {
            return [];
        }
        
        $result = [];
        foreach ($files as $file) {
            $result[] = [
                'name' => basename($file),
                'type' => 'file',
            ];
        }
        
        return $result;
    }
    
    public function delete($remote_path, $settings) {
        $conn = $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        $result = ftp_delete($conn, $remote_file);
        
        ftp_close($conn);
        
        if (!$result) {
            throw new Exception('FTP delete failed');
        }
        
        return true;
    }
    
    public function file_exists($remote_path, $settings) {
        try {
            $conn = $this->connect($settings);
            $remote_file = $this->get_remote_path($remote_path, $settings);
            $size = ftp_size($conn, $remote_file);
            ftp_close($conn);
            return $size !== -1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function get_file_size($remote_path, $settings) {
        $conn = $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        $size = ftp_size($conn, $remote_file);
        
        ftp_close($conn);
        
        if ($size === -1) {
            throw new Exception('Cannot get file size');
        }
        
        return $size;
    }
    
    private function connect($settings) {
        if (!function_exists('ftp_connect')) {
            throw new Exception('FTP extension not available');
        }
        
        $host = $settings['host'] ?? '';
        $port = $settings['port'] ?? 21;
        $username = $settings['username'] ?? '';
        $password = $settings['password'] ?? '';
        $passive = $settings['passive'] ?? true;
        
        if (empty($host) || empty($username)) {
            throw new Exception('FTP host and username are required');
        }
        
        $conn = ftp_connect($host, $port, 30);
        if (!$conn) {
            throw new Exception('Cannot connect to FTP server');
        }
        
        if (!ftp_login($conn, $username, $password)) {
            ftp_close($conn);
            throw new Exception('FTP login failed');
        }
        
        if ($passive) {
            ftp_pasv($conn, true);
        }
        
        return $conn;
    }
    
    private function get_remote_path($path, $settings) {
        $directory = $settings['directory'] ?? '/';
        return rtrim($directory, '/') . '/' . ltrim($path, '/');
    }
    
    private function create_remote_directory($path, $conn) {
        $parts = explode('/', trim($path, '/'));
        $current = '';
        
        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            $current .= '/' . $part;
            @ftp_mkdir($conn, $current);
        }
    }
}