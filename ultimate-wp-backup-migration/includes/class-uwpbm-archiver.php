<?php
/**
 * Archive handling class
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Archive creation and extraction handler
 */
class UWPBM_Archiver {
    
    /**
     * Create archive from directory
     *
     * @param string $source_dir Source directory
     * @param string $archive_path Archive file path
     * @throws Exception
     */
    public function create_archive($source_dir, $archive_path) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available. Please install php-zip extension.');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            throw new Exception('Cannot create archive: ' . $this->get_zip_error($result));
        }
        
        $this->add_directory_to_zip($zip, $source_dir, '');
        
        $zip->close();
        
        if (!file_exists($archive_path)) {
            throw new Exception('Archive creation failed');
        }
    }
    
    /**
     * Extract archive to directory
     *
     * @param string $archive_path Archive file path
     * @param string $destination_dir Destination directory
     * @throws Exception
     */
    public function extract_archive($archive_path, $destination_dir) {
        if (!file_exists($archive_path)) {
            throw new Exception('Archive file not found: ' . $archive_path);
        }
        
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available. Please install php-zip extension.');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($archive_path);
        
        if ($result !== TRUE) {
            throw new Exception('Cannot open archive: ' . $this->get_zip_error($result));
        }
        
        if (!is_dir($destination_dir)) {
            wp_mkdir_p($destination_dir);
        }
        
        $result = $zip->extractTo($destination_dir);
        $zip->close();
        
        if (!$result) {
            throw new Exception('Archive extraction failed');
        }
    }
    
    /**
     * Get archive information
     *
     * @param string $archive_path Archive file path
     * @return array Archive information
     * @throws Exception
     */
    public function get_archive_info($archive_path) {
        if (!file_exists($archive_path)) {
            throw new Exception('Archive file not found: ' . $archive_path);
        }
        
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available. Please install php-zip extension.');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($archive_path);
        
        if ($result !== TRUE) {
            throw new Exception('Cannot open archive: ' . $this->get_zip_error($result));
        }
        
        $info = [
            'file_count' => $zip->numFiles,
            'size' => filesize($archive_path),
            'files' => [],
        ];
        
        // Get file list (limit to first 100 files for performance)
        $max_files = min(100, $zip->numFiles);
        for ($i = 0; $i < $max_files; $i++) {
            $file_info = $zip->statIndex($i);
            $info['files'][] = [
                'name' => $file_info['name'],
                'size' => $file_info['size'],
                'compressed_size' => $file_info['comp_size'],
            ];
        }
        
        $zip->close();
        
        return $info;
    }
    
    /**
     * Validate archive integrity
     *
     * @param string $archive_path Archive file path
     * @return bool True if valid
     */
    public function validate_archive($archive_path) {
        try {
            if (!file_exists($archive_path)) {
                return false;
            }
            
            if (!class_exists('ZipArchive')) {
                return false;
            }
            
            $zip = new ZipArchive();
            $result = $zip->open($archive_path, ZipArchive::CHECKCONS);
            
            if ($result !== TRUE) {
                return false;
            }
            
            $zip->close();
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Add directory to ZIP archive recursively
     *
     * @param ZipArchive $zip ZIP archive object
     * @param string $source_dir Source directory
     * @param string $relative_path Relative path in archive
     */
    private function add_directory_to_zip($zip, $source_dir, $relative_path) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();
            $archive_path = $relative_path . str_replace($source_dir . DIRECTORY_SEPARATOR, '', $file_path);
            
            // Normalize path separators for cross-platform compatibility
            $archive_path = str_replace('\\', '/', $archive_path);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($archive_path . '/');
            } else {
                $zip->addFile($file_path, $archive_path);
            }
        }
    }
    
    /**
     * Get ZIP error message
     *
     * @param int $error_code ZIP error code
     * @return string Error message
     */
    private function get_zip_error($error_code) {
        switch ($error_code) {
            case ZipArchive::ER_OK:
                return 'No error';
            case ZipArchive::ER_MULTIDISK:
                return 'Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME:
                return 'Renaming temporary file failed';
            case ZipArchive::ER_CLOSE:
                return 'Closing zip archive failed';
            case ZipArchive::ER_SEEK:
                return 'Seek error';
            case ZipArchive::ER_READ:
                return 'Read error';
            case ZipArchive::ER_WRITE:
                return 'Write error';
            case ZipArchive::ER_CRC:
                return 'CRC error';
            case ZipArchive::ER_ZIPCLOSED:
                return 'Containing zip archive was closed';
            case ZipArchive::ER_NOENT:
                return 'No such file';
            case ZipArchive::ER_EXISTS:
                return 'File already exists';
            case ZipArchive::ER_OPEN:
                return 'Can\'t open file';
            case ZipArchive::ER_TMPOPEN:
                return 'Failure to create temporary file';
            case ZipArchive::ER_ZLIB:
                return 'Zlib error';
            case ZipArchive::ER_MEMORY:
                return 'Memory allocation failure';
            case ZipArchive::ER_CHANGED:
                return 'Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP:
                return 'Compression method not supported';
            case ZipArchive::ER_EOF:
                return 'Premature EOF';
            case ZipArchive::ER_INVAL:
                return 'Invalid argument';
            case ZipArchive::ER_NOZIP:
                return 'Not a zip archive';
            case ZipArchive::ER_INTERNAL:
                return 'Internal error';
            case ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent';
            case ZipArchive::ER_REMOVE:
                return 'Can\'t remove file';
            case ZipArchive::ER_DELETED:
                return 'Entry has been deleted';
            default:
                return 'Unknown error code: ' . $error_code;
        }
    }
}