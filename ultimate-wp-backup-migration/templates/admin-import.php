<?php
/**
 * Admin import template
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Import Backup', 'ultimate-wp-backup-migration'); ?></h1>
    
    <div class="uwpbm-import-options">
        <!-- Import from Existing Backup -->
        <div class="uwpbm-card">
            <h2><?php _e('Import from Existing Backup', 'ultimate-wp-backup-migration'); ?></h2>
            
            <?php if (!empty($available_backups)): ?>
                <form id="uwpbm-import-existing-form" class="uwpbm-form" method="post">
                    <?php wp_nonce_field('uwpbm_import', '_wpnonce'); ?>
                    
                    <div class="uwpbm-form-row">
                        <label for="backup_id"><?php _e('Select Backup', 'ultimate-wp-backup-migration'); ?></label>
                        <select id="backup_id" name="backup_id" required>
                            <option value=""><?php _e('Choose a backup...', 'ultimate-wp-backup-migration'); ?></option>
                            <?php foreach ($available_backups as $backup): ?>
                                <option value="<?php echo intval($backup->id); ?>">
                                    <?php echo esc_html($backup->name); ?> 
                                    (<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($backup->created_at))); ?>)
                                    <?php if ($backup->size): ?>
                                        - <?php echo size_format($backup->size); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="uwpbm-form-row">
                        <label>
                            <input type="checkbox" name="overwrite_database" value="1" checked>
                            <?php _e('Overwrite Database', 'ultimate-wp-backup-migration'); ?>
                        </label>
                        <p class="description"><?php _e('Replace current database with backup data', 'ultimate-wp-backup-migration'); ?></p>
                    </div>
                    
                    <div class="uwpbm-form-row">
                        <label>
                            <input type="checkbox" name="overwrite_files" value="1" checked>
                            <?php _e('Overwrite Files', 'ultimate-wp-backup-migration'); ?>
                        </label>
                        <p class="description"><?php _e('Replace current files with backup files', 'ultimate-wp-backup-migration'); ?></p>
                    </div>
                    
                    <div class="uwpbm-form-row">
                        <button type="button" class="button button-primary button-large uwpbm-restore-backup">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Restore Backup', 'ultimate-wp-backup-migration'); ?>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p><?php _e('No backups available. Create a backup first.', 'ultimate-wp-backup-migration'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=uwpbm-export'); ?>" class="button button-primary">
                    <?php _e('Create Backup', 'ultimate-wp-backup-migration'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Import from File -->
        <div class="uwpbm-card">
            <h2><?php _e('Import from File', 'ultimate-wp-backup-migration'); ?></h2>
            
            <form id="uwpbm-import-file-form" class="uwpbm-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('uwpbm_import_file', '_wpnonce'); ?>
                
                <div class="uwpbm-form-row">
                    <label for="backup_file"><?php _e('Backup File', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="file" id="backup_file" name="backup_file" accept=".zip" required>
                    <p class="description"><?php _e('Select a .zip backup file to import', 'ultimate-wp-backup-migration'); ?></p>
                </div>
                
                <div class="uwpbm-form-row">
                    <label>
                        <input type="checkbox" name="overwrite_database" value="1" checked>
                        <?php _e('Overwrite Database', 'ultimate-wp-backup-migration'); ?>
                    </label>
                </div>
                
                <div class="uwpbm-form-row">
                    <label>
                        <input type="checkbox" name="overwrite_files" value="1" checked>
                        <?php _e('Overwrite Files', 'ultimate-wp-backup-migration'); ?>
                    </label>
                </div>
                
                <div class="uwpbm-form-row">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Upload & Restore', 'ultimate-wp-backup-migration'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Import from Remote -->
        <div class="uwpbm-card">
            <h2><?php _e('Import from Remote Location', 'ultimate-wp-backup-migration'); ?></h2>
            
            <form id="uwpbm-import-remote-form" class="uwpbm-form" method="post">
                <?php wp_nonce_field('uwpbm_import_remote', '_wpnonce'); ?>
                
                <div class="uwpbm-form-row">
                    <label for="remote_protocol"><?php _e('Protocol', 'ultimate-wp-backup-migration'); ?></label>
                    <select id="remote_protocol" name="protocol" required>
                        <?php foreach ($protocols as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="uwpbm-form-row">
                    <label for="remote_path"><?php _e('Remote File Path', 'ultimate-wp-backup-migration'); ?></label>
                    <input type="text" id="remote_path" name="remote_path" placeholder="/backups/backup-2024-01-01.zip" required>
                    <p class="description"><?php _e('Full path to the backup file on remote server', 'ultimate-wp-backup-migration'); ?></p>
                </div>
                
                <div class="uwpbm-form-row">
                    <label>
                        <input type="checkbox" name="overwrite_database" value="1" checked>
                        <?php _e('Overwrite Database', 'ultimate-wp-backup-migration'); ?>
                    </label>
                </div>
                
                <div class="uwpbm-form-row">
                    <label>
                        <input type="checkbox" name="overwrite_files" value="1" checked>
                        <?php _e('Overwrite Files', 'ultimate-wp-backup-migration'); ?>
                    </label>
                </div>
                
                <div class="uwpbm-form-row">
                    <button type="button" class="button button-primary button-large uwpbm-import-remote">
                        <span class="dashicons dashicons-cloud"></span>
                        <?php _e('Import from Remote', 'ultimate-wp-backup-migration'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Warning Notice -->
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Warning:', 'ultimate-wp-backup-migration'); ?></strong>
            <?php _e('Importing a backup will overwrite your current site data. Make sure you have a backup of your current site before proceeding.', 'ultimate-wp-backup-migration'); ?>
        </p>
    </div>
</div>

<style>
.uwpbm-import-options {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-top: 20px;
}

@media (min-width: 1200px) {
    .uwpbm-import-options {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

.uwpbm-card h2 {
    margin-top: 0;
    color: #23282d;
    font-size: 16px;
}

.uwpbm-form-row {
    margin-bottom: 15px;
}

.uwpbm-form-row:last-child {
    margin-top: 20px;
}
</style>