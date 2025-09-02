<?php
/**
 * Admin settings template
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Settings', 'ultimate-wp-backup-migration'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('uwpbm_settings', '_wpnonce'); ?>
        
        <!-- General Settings -->
        <div class="uwpbm-form-section">
            <h3><?php _e('General Settings', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label for="max_execution_time"><?php _e('Max Execution Time (seconds)', 'ultimate-wp-backup-migration'); ?></label>
                <input type="number" id="max_execution_time" name="max_execution_time" value="<?php echo intval($settings['max_execution_time']); ?>" min="60" max="3600">
                <p class="description"><?php _e('Maximum time allowed for backup operations', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="memory_limit"><?php _e('Memory Limit', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="memory_limit" name="memory_limit" value="<?php echo esc_attr($settings['memory_limit']); ?>">
                <p class="description"><?php _e('Memory limit for backup operations (e.g., 512M, 1G)', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="chunk_size"><?php _e('Chunk Size (bytes)', 'ultimate-wp-backup-migration'); ?></label>
                <input type="number" id="chunk_size" name="chunk_size" value="<?php echo intval($settings['chunk_size']); ?>" min="1048576" max="104857600">
                <p class="description"><?php _e('Size of data chunks for processing large files', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="backup_retention"><?php _e('Backup Retention (days)', 'ultimate-wp-backup-migration'); ?></label>
                <input type="number" id="backup_retention" name="backup_retention" value="<?php echo intval($settings['backup_retention']); ?>" min="1" max="365">
                <p class="description"><?php _e('Number of days to keep backups before automatic deletion', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="enable_logging" value="1" <?php checked($settings['enable_logging']); ?>>
                    <?php _e('Enable Logging', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Log backup operations for debugging', 'ultimate-wp-backup-migration'); ?></p>
            </div>
        </div>
        
        <!-- FTP Settings -->
        <div class="uwpbm-form-section">
            <h3><?php _e('FTP Settings', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label for="ftp_host"><?php _e('Host', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="ftp_host" name="ftp_host" value="<?php echo esc_attr(get_option('uwpbm_ftp_host', '')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="ftp_port"><?php _e('Port', 'ultimate-wp-backup-migration'); ?></label>
                <input type="number" id="ftp_port" name="ftp_port" value="<?php echo intval(get_option('uwpbm_ftp_port', 21)); ?>" min="1" max="65535">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="ftp_username"><?php _e('Username', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="ftp_username" name="ftp_username" value="<?php echo esc_attr(get_option('uwpbm_ftp_username', '')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="ftp_password"><?php _e('Password', 'ultimate-wp-backup-migration'); ?></label>
                <input type="password" id="ftp_password" name="ftp_password" value="<?php echo esc_attr(get_option('uwpbm_ftp_password', '')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="ftp_directory"><?php _e('Directory', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="ftp_directory" name="ftp_directory" value="<?php echo esc_attr(get_option('uwpbm_ftp_directory', '/backups')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <button type="button" class="button uwpbm-test-ftp">
                    <?php _e('Test FTP Connection', 'ultimate-wp-backup-migration'); ?>
                </button>
            </div>
        </div>
        
        <!-- SFTP Settings -->
        <div class="uwpbm-form-section">
            <h3><?php _e('SFTP Settings', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label for="sftp_host"><?php _e('Host', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="sftp_host" name="sftp_host" value="<?php echo esc_attr(get_option('uwpbm_sftp_host', '')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="sftp_port"><?php _e('Port', 'ultimate-wp-backup-migration'); ?></label>
                <input type="number" id="sftp_port" name="sftp_port" value="<?php echo intval(get_option('uwpbm_sftp_port', 22)); ?>" min="1" max="65535">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="sftp_username"><?php _e('Username', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="sftp_username" name="sftp_username" value="<?php echo esc_attr(get_option('uwpbm_sftp_username', '')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="sftp_auth_method"><?php _e('Authentication', 'ultimate-wp-backup-migration'); ?></label>
                <select id="sftp_auth_method" name="sftp_auth_method">
                    <option value="password" <?php selected(get_option('uwpbm_sftp_auth_method', 'password'), 'password'); ?>><?php _e('Password', 'ultimate-wp-backup-migration'); ?></option>
                    <option value="key" <?php selected(get_option('uwpbm_sftp_auth_method', 'password'), 'key'); ?>><?php _e('Private Key', 'ultimate-wp-backup-migration'); ?></option>
                </select>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="sftp_password"><?php _e('Password', 'ultimate-wp-backup-migration'); ?></label>
                <input type="password" id="sftp_password" name="sftp_password" value="<?php echo esc_attr(get_option('uwpbm_sftp_password', '')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <label for="sftp_directory"><?php _e('Directory', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="sftp_directory" name="sftp_directory" value="<?php echo esc_attr(get_option('uwpbm_sftp_directory', '/backups')); ?>">
            </div>
            
            <div class="uwpbm-form-row">
                <button type="button" class="button uwpbm-test-sftp">
                    <?php _e('Test SFTP Connection', 'ultimate-wp-backup-migration'); ?>
                </button>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings', 'ultimate-wp-backup-migration'); ?>">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('.uwpbm-test-ftp').on('click', function() {
        var settings = {
            host: $('#ftp_host').val(),
            port: $('#ftp_port').val(),
            username: $('#ftp_username').val(),
            password: $('#ftp_password').val(),
            directory: $('#ftp_directory').val()
        };
        
        testConnection('ftp', settings, $(this));
    });
    
    $('.uwpbm-test-sftp').on('click', function() {
        var settings = {
            host: $('#sftp_host').val(),
            port: $('#sftp_port').val(),
            username: $('#sftp_username').val(),
            password: $('#sftp_password').val(),
            directory: $('#sftp_directory').val(),
            auth_method: $('#sftp_auth_method').val()
        };
        
        testConnection('sftp', settings, $(this));
    });
    
    function testConnection(protocol, settings, button) {
        button.prop('disabled', true).text('Testing...');
        
        $.post(ajaxurl, {
            action: 'uwpbm_test_connection',
            nonce: '<?php echo wp_create_nonce('uwpbm_ajax'); ?>',
            protocol: protocol,
            settings: settings
        })
        .done(function(response) {
            if (response.success) {
                alert('Connection successful!');
            } else {
                alert('Connection failed: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Connection test failed. Please try again.');
        })
        .always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    }
});
</script>