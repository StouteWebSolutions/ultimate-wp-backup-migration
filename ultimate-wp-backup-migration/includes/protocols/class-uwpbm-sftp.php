<?php
/**
 * SFTP protocol handler
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_SFTP implements UWPBM_Protocol_Interface {
    
    private $connection;
    private $sftp;
    
    public function test_connection($settings) {
        $this->connect($settings);
        
        $result = [
            'status' => 'success',
            'server' => $settings['host'],
            'port' => $settings['port'],
            'directory' => $settings['directory'] ?? '/',
        ];
        
        $this->disconnect();
        return $result;
    }
    
    public function upload($local_file, $remote_path, $settings) {
        if (!file_exists($local_file)) {
            throw new Exception('Local file not found: ' . $local_file);
        }
        
        $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        $this->create_remote_directory(dirname($remote_file));
        
        $result = ssh2_scp_send($this->connection, $local_file, $remote_file);
        $this->disconnect();
        
        if (!$result) {
            throw new Exception('SFTP upload failed');
        }
        
        return true;
    }
    
    public function download($remote_path, $local_file, $settings) {
        $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        
        $local_dir = dirname($local_file);
        if (!is_dir($local_dir)) {
            wp_mkdir_p($local_dir);
        }
        
        $result = ssh2_scp_recv($this->connection, $remote_file, $local_file);
        $this->disconnect();
        
        if (!$result) {
            throw new Exception('SFTP download failed');
        }
        
        return true;
    }
    
    public function list_files($remote_path, $settings) {
        $this->connect($settings);
        
        $remote_dir = $this->get_remote_path($remote_path, $settings);
        $handle = ssh2_sftp_opendir($this->sftp, $remote_dir);
        
        if (!$handle) {
            $this->disconnect();
            return [];
        }
        
        $files = [];
        while (($file = ssh2_sftp_readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') continue;
            
            $files[] = [
                'name' => $file,
                'type' => 'file',
            ];
        }
        
        ssh2_sftp_closedir($handle);
        $this->disconnect();
        
        return $files;
    }
    
    public function delete($remote_path, $settings) {
        $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        $result = ssh2_sftp_unlink($this->sftp, $remote_file);
        
        $this->disconnect();
        
        if (!$result) {
            throw new Exception('SFTP delete failed');
        }
        
        return true;
    }
    
    public function file_exists($remote_path, $settings) {
        try {
            $this->connect($settings);
            $remote_file = $this->get_remote_path($remote_path, $settings);
            $stat = ssh2_sftp_stat($this->sftp, $remote_file);
            $this->disconnect();
            return $stat !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function get_file_size($remote_path, $settings) {
        $this->connect($settings);
        
        $remote_file = $this->get_remote_path($remote_path, $settings);
        $stat = ssh2_sftp_stat($this->sftp, $remote_file);
        
        $this->disconnect();
        
        if (!$stat) {
            throw new Exception('Cannot get file size');
        }
        
        return $stat['size'];
    }
    
    private function connect($settings) {
        if (!function_exists('ssh2_connect')) {
            throw new Exception('SSH2 extension not available');
        }
        
        $host = $settings['host'] ?? '';
        $port = $settings['port'] ?? 22;
        $username = $settings['username'] ?? '';
        $auth_method = $settings['auth_method'] ?? 'password';
        
        if (empty($host) || empty($username)) {
            throw new Exception('SFTP host and username are required');
        }
        
        $this->connection = ssh2_connect($host, $port);
        if (!$this->connection) {
            throw new Exception('Cannot connect to SFTP server');
        }
        
        if ($auth_method === 'key') {
            $private_key = $settings['private_key'] ?? '';
            if (empty($private_key)) {
                throw new Exception('Private key is required for key authentication');
            }
            
            $key_file = tempnam(sys_get_temp_dir(), 'uwpbm_key');
            file_put_contents($key_file, $private_key);
            
            $result = ssh2_auth_pubkey_file($this->connection, $username, null, $key_file);
            unlink($key_file);
        } else {
            $password = $settings['password'] ?? '';
            $result = ssh2_auth_password($this->connection, $username, $password);
        }
        
        if (!$result) {
            throw new Exception('SFTP authentication failed');
        }
        
        $this->sftp = ssh2_sftp($this->connection);
        if (!$this->sftp) {
            throw new Exception('Cannot initialize SFTP subsystem');
        }
    }
    
    private function disconnect() {
        if ($this->connection) {
            ssh2_disconnect($this->connection);
            $this->connection = null;
            $this->sftp = null;
        }
    }
    
    private function get_remote_path($path, $settings) {
        $directory = $settings['directory'] ?? '/';
        return rtrim($directory, '/') . '/' . ltrim($path, '/');
    }
    
    private function create_remote_directory($path) {
        $parts = explode('/', trim($path, '/'));
        $current = '';
        
        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            $current .= '/' . $part;
            @ssh2_sftp_mkdir($this->sftp, $current, 0755, true);
        }
    }
}