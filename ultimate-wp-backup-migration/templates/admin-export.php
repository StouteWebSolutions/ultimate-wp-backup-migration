<?php
/**
 * Admin export template
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Export Backup', 'ultimate-wp-backup-migration'); ?></h1>
    
    <form id="uwpbm-export-form" class="uwpbm-form" method="post">
        <?php wp_nonce_field('uwpbm_export', '_wpnonce'); ?>
        
        <!-- Backup Details -->
        <div class="uwpbm-form-section">
            <h3><?php _e('Backup Details', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label for="backup_name"><?php _e('Backup Name', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="backup_name" name="name" value="backup-<?php echo date('Y-m-d-H-i-s'); ?>" required>
                <p class="description"><?php _e('Enter a name for this backup', 'ultimate-wp-backup-migration'); ?></p>
            </div>
        </div>
        
        <!-- What to Include -->
        <div class="uwpbm-form-section">
            <h3><?php _e('What to Include', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="include_database" value="1" <?php checked($export_options['include_database']); ?>>
                    <?php _e('Database', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Include all database tables and content', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="include_media" value="1" <?php checked($export_options['include_media']); ?>>
                    <?php _e('Media Files', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Include uploads directory (images, videos, documents)', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="include_plugins" value="1" <?php checked($export_options['include_plugins']); ?>>
                    <?php _e('Plugins', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Include all installed plugins', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="include_themes" value="1" <?php checked($export_options['include_themes']); ?>>
                    <?php _e('Themes', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Include all installed themes', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="include_uploads" value="1" <?php checked($export_options['include_uploads']); ?>>
                    <?php _e('Other Uploads', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Include other files in wp-content/uploads', 'ultimate-wp-backup-migration'); ?></p>
            </div>
        </div>
        
        <!-- Storage Destination -->
        <div class="uwpbm-form-section">
            <h3><?php _e('Storage Destination', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label for="protocol"><?php _e('Storage Type', 'ultimate-wp-backup-migration'); ?></label>
                <select id="protocol" name="protocol" required>
                    <?php foreach ($protocols as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Choose where to store the backup', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <!-- Local Storage Settings -->
            <div id="local-settings" class="uwpbm-protocol-settings">
                <div class="uwpbm-form-row">
                    <label for="local_storage_path"><?php _e('Storage Path', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="local_storage_path" name="local_storage_path" placeholder="<?php echo esc_attr(wp_upload_dir()['basedir'] . '/uwpbm-backups'); ?>">
                    <p class="description"><?php _e('Local directory to store backups (leave empty for default)', 'ultimate-wp-backup-migration'); ?></p>
                </div>
            </div>
            
            <!-- FTP Settings -->
            <div id="ftp-settings" class="uwpbm-protocol-settings" style="display: none;">
                <div class="uwpbm-form-row">
                    <label for="ftp_host"><?php _e('FTP Host', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="ftp_host" name="ftp_host" placeholder="ftp.example.com">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="ftp_port"><?php _e('Port', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="number" id="ftp_port" name="ftp_port" value="21" min="1" max="65535">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="ftp_username"><?php _e('Username', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="ftp_username" name="ftp_username">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="ftp_password"><?php _e('Password', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="password" id="ftp_password" name="ftp_password">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="ftp_directory"><?php _e('Directory', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="ftp_directory" name="ftp_directory" placeholder="/backups">
                    <p class="description"><?php _e('Remote directory to store backups', 'ultimate-wp-backup-migration'); ?></p>
                </div>
                
                <div class="uwpbm-form-row">
                    <label>
                        <input type="checkbox" name="ftp_passive" value="1" checked>
                        <?php _e('Use Passive Mode', 'ultimate-wp-backup-migration'); ?>
                    </label>
                </div>
            </div>
            
            <!-- SFTP Settings -->
            <div id="sftp-settings" class="uwpbm-protocol-settings" style="display: none;">
                <div class="uwpbm-form-row">
                    <label for="sftp_host"><?php _e('SFTP Host', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="sftp_host" name="sftp_host" placeholder="sftp.example.com">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="sftp_port"><?php _e('Port', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="number" id="sftp_port" name="sftp_port" value="22" min="1" max="65535">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="sftp_username"><?php _e('Username', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="sftp_username" name="sftp_username">
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="sftp_auth_method"><?php _e('Authentication Method', 'ultimate-wp-backup-migration'); ?></label>
                    <select id="sftp_auth_method" name="sftp_auth_method">
                        <option value="password"><?php _e('Password', 'ultimate-wp-backup-migration'); ?></option>
                        <option value="key"><?php _e('Private Key', 'ultimate-wp-backup-migration'); ?></option>
                    </select>
                </div>
                
                <div id="sftp-password-auth" class="uwpbm-form-row">
                    <label for="sftp_password"><?php _e('Password', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="password" id="sftp_password" name="sftp_password">
                </div>
                
                <div id="sftp-key-auth" class="uwpbm-form-row" style="display: none;">
                    <label for="sftp_private_key"><?php _e('Private Key', 'ultimate-wp-backup-migration'); ?></label>
                    <textarea id="sftp_private_key" name="sftp_private_key" rows="5" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                    <p class="description"><?php _e('Paste your private key content here', 'ultimate-wp-backup-migration'); ?></p>
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="sftp_directory"><?php _e('Directory', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="sftp_directory" name="sftp_directory" placeholder="/backups">
                    <p class="description"><?php _e('Remote directory to store backups', 'ultimate-wp-backup-migration'); ?></p>
                </div>
            </div>
            
            <div class="uwpbm-form-row">
                <button type="button" class="button uwpbm-test-connection">
                    <?php _e('Test Connection', 'ultimate-wp-backup-migration'); ?>
                </button>
            </div>
        </div>
        
        <!-- Advanced Options -->
        <div class="uwpbm-form-section">
            <h3><?php _e('Advanced Options', 'ultimate-wp-backup-migration'); ?></h3>
            
            <div class="uwpbm-form-row">
                <label for="compression"><?php _e('Compression', 'ultimate-wp-backup-migration'); ?></label>
                <select id="compression" name="compression">
                    <option value="zip"><?php _e('ZIP (Recommended)', 'ultimate-wp-backup-migration'); ?></option>
                </select>
                <p class="description"><?php _e('Archive compression format', 'ultimate-wp-backup-migration'); ?></p>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="email_notification" value="1">
                    <?php _e('Email Notification', 'ultimate-wp-backup-migration'); ?>
                </label>
                <p class="description"><?php _e('Send email when backup is completed', 'ultimate-wp-backup-migration'); ?></p>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="uwpbm-form-section">
            <button type="button" class="button button-primary button-large uwpbm-start-backup">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Start Backup', 'ultimate-wp-backup-migration'); ?>
            </button>
            
            <a href="<?php echo admin_url('admin.php?page=uwpbm-dashboard'); ?>" class="button button-secondary button-large">
                <?php _e('Cancel', 'ultimate-wp-backup-migration'); ?>
            </a>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Protocol switching
    $('#protocol').on('change', function() {
        $('.uwpbm-protocol-settings').hide();
        $('#' + $(this).val() + '-settings').show();
    });
    
    // SFTP authentication method switching
    $('#sftp_auth_method').on('change', function() {
        if ($(this).val() === 'key') {
            $('#sftp-password-auth').hide();
            $('#sftp-key-auth').show();
        } else {
            $('#sftp-password-auth').show();
            $('#sftp-key-auth').hide();
        }
    });
    
    // Initialize
    $('#protocol').trigger('change');
});
</script>

<style>
.uwpbm-protocol-settings {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.uwpbm-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.uwpbm-form-row input[type="checkbox"] + label {
    display: inline;
    margin-left: 5px;
    font-weight: normal;
}

.uwpbm-form-row input,
.uwpbm-form-row select,
.uwpbm-form-row textarea {
    width: 100%;
    max-width: 400px;
}

.uwpbm-form-row textarea {
    font-family: monospace;
    font-size: 12px;
}
</style>