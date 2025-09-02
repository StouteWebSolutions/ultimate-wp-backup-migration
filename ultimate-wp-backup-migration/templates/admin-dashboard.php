<?php
/**
 * Admin dashboard template
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Ultimate Backup Migration Dashboard', 'ultimate-wp-backup-migration'); ?></h1>
    
    <div class="uwpbm-dashboard">
        <!-- Quick Actions -->
        <div class="uwpbm-card">
            <h2><?php _e('Quick Actions', 'ultimate-wp-backup-migration'); ?></h2>
            <div class="uwpbm-actions">
                <a href="<?php echo admin_url('admin.php?page=uwpbm-export'); ?>" class="button button-primary button-large">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Create Backup', 'ultimate-wp-backup-migration'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=uwpbm-import'); ?>" class="button button-secondary button-large">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Restore Backup', 'ultimate-wp-backup-migration'); ?>
                </a>
            </div>
        </div>
        
        <!-- Status Overview -->
        <div class="uwpbm-card">
            <h2><?php _e('Status Overview', 'ultimate-wp-backup-migration'); ?></h2>
            <div class="uwpbm-status-grid">
                <div class="uwpbm-status-item">
                    <h3><?php _e('Last Backup', 'ultimate-wp-backup-migration'); ?></h3>
                    <p class="uwpbm-status-value">
                        <?php 
                        if (!empty($recent_backups)) {
                            echo esc_html(date_i18n(get_option('date_format'), strtotime($recent_backups[0]->created_at)));
                        } else {
                            _e('No backups yet', 'ultimate-wp-backup-migration');
                        }
                        ?>
                    </p>
                </div>
                
                <div class="uwpbm-status-item">
                    <h3><?php _e('Next Scheduled', 'ultimate-wp-backup-migration'); ?></h3>
                    <p class="uwpbm-status-value">
                        <?php echo $next_scheduled ? esc_html($next_scheduled) : __('Not scheduled', 'ultimate-wp-backup-migration'); ?>
                    </p>
                </div>
                
                <div class="uwpbm-status-item">
                    <h3><?php _e('Storage Used', 'ultimate-wp-backup-migration'); ?></h3>
                    <p class="uwpbm-status-value"><?php echo esc_html($storage_usage['used']); ?></p>
                    <div class="uwpbm-progress-bar">
                        <div class="uwpbm-progress-fill" style="width: <?php echo intval($storage_usage['percentage']); ?>%"></div>
                    </div>
                </div>
                
                <div class="uwpbm-status-item">
                    <h3><?php _e('Total Backups', 'ultimate-wp-backup-migration'); ?></h3>
                    <p class="uwpbm-status-value"><?php echo count($recent_backups); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Recent Backups -->
        <div class="uwpbm-card">
            <h2><?php _e('Recent Backups', 'ultimate-wp-backup-migration'); ?></h2>
            <?php if (!empty($recent_backups)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'ultimate-wp-backup-migration'); ?></th>
                            <th><?php _e('Type', 'ultimate-wp-backup-migration'); ?></th>
                            <th><?php _e('Size', 'ultimate-wp-backup-migration'); ?></th>
                            <th><?php _e('Status', 'ultimate-wp-backup-migration'); ?></th>
                            <th><?php _e('Created', 'ultimate-wp-backup-migration'); ?></th>
                            <th><?php _e('Actions', 'ultimate-wp-backup-migration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_backups as $backup): ?>
                            <tr>
                                <td><strong><?php echo esc_html($backup->name); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($backup->type)); ?></td>
                                <td><?php echo $backup->size ? size_format($backup->size) : '—'; ?></td>
                                <td>
                                    <span class="uwpbm-status uwpbm-status-<?php echo esc_attr($backup->status); ?>">
                                        <?php echo esc_html(ucfirst($backup->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($backup->created_at))); ?></td>
                                <td>
                                    <?php if ($backup->status === 'completed'): ?>
                                        <a href="#" class="button button-small uwpbm-restore-backup" data-backup-id="<?php echo intval($backup->id); ?>">
                                            <?php _e('Restore', 'ultimate-wp-backup-migration'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="#" class="button button-small uwpbm-delete-backup" data-backup-id="<?php echo intval($backup->id); ?>">
                                        <?php _e('Delete', 'ultimate-wp-backup-migration'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No backups found. Create your first backup to get started!', 'ultimate-wp-backup-migration'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=uwpbm-export'); ?>" class="button button-primary">
                    <?php _e('Create First Backup', 'ultimate-wp-backup-migration'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- System Information -->
        <div class="uwpbm-card">
            <h2><?php _e('System Information', 'ultimate-wp-backup-migration'); ?></h2>
            <div class="uwpbm-system-info">
                <div class="uwpbm-info-row">
                    <span class="uwpbm-info-label"><?php _e('WordPress Version:', 'ultimate-wp-backup-migration'); ?></span>
                    <span class="uwpbm-info-value"><?php echo get_bloginfo('version'); ?></span>
                </div>
                <div class="uwpbm-info-row">
                    <span class="uwpbm-info-label"><?php _e('PHP Version:', 'ultimate-wp-backup-migration'); ?></span>
                    <span class="uwpbm-info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="uwpbm-info-row">
                    <span class="uwpbm-info-label"><?php _e('Memory Limit:', 'ultimate-wp-backup-migration'); ?></span>
                    <span class="uwpbm-info-value"><?php echo ini_get('memory_limit'); ?></span>
                </div>
                <div class="uwpbm-info-row">
                    <span class="uwpbm-info-label"><?php _e('Max Execution Time:', 'ultimate-wp-backup-migration'); ?></span>
                    <span class="uwpbm-info-value"><?php echo ini_get('max_execution_time'); ?>s</span>
                </div>
                <div class="uwpbm-info-row">
                    <span class="uwpbm-info-label"><?php _e('Site Size:', 'ultimate-wp-backup-migration'); ?></span>
                    <span class="uwpbm-info-value"><?php echo size_format(get_dirsize(ABSPATH)); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.uwpbm-dashboard {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.uwpbm-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.uwpbm-card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.uwpbm-actions {
    display: flex;
    gap: 10px;
}

.uwpbm-actions .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

.uwpbm-status-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.uwpbm-status-item h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
}

.uwpbm-status-value {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #23282d;
}

.uwpbm-progress-bar {
    width: 100%;
    height: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    margin-top: 5px;
    overflow: hidden;
}

.uwpbm-progress-fill {
    height: 100%;
    background: #00a32a;
    transition: width 0.3s ease;
}

.uwpbm-status {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.uwpbm-status-completed {
    background: #d4edda;
    color: #155724;
}

.uwpbm-status-running {
    background: #fff3cd;
    color: #856404;
}

.uwpbm-status-failed {
    background: #f8d7da;
    color: #721c24;
}

.uwpbm-info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.uwpbm-info-row:last-child {
    border-bottom: none;
}

.uwpbm-info-label {
    font-weight: 600;
}

@media (max-width: 782px) {
    .uwpbm-dashboard {
        grid-template-columns: 1fr;
    }
    
    .uwpbm-status-grid {
        grid-template-columns: 1fr;
    }
    
    .uwpbm-actions {
        flex-direction: column;
    }
}
</style>