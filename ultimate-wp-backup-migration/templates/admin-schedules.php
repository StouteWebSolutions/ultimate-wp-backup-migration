<?php
/**
 * Admin schedules template
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Backup Schedules', 'ultimate-wp-backup-migration'); ?></h1>
    
    <!-- Real-time Monitoring -->
    <div class="uwpbm-card">
        <h2><?php _e('Real-time Monitoring', 'ultimate-wp-backup-migration'); ?></h2>
        <p><?php _e('Monitor your site for changes and trigger automatic backups.', 'ultimate-wp-backup-migration'); ?></p>
        
        <div class="uwpbm-form-row">
            <label class="uwpbm-toggle">
                <input type="checkbox" id="monitor-toggle" <?php checked($monitor_enabled); ?>>
                <span class="uwpbm-toggle-slider"></span>
                <?php _e('Enable Real-time Monitoring', 'ultimate-wp-backup-migration'); ?>
            </label>
        </div>
        
        <?php if (!empty($monitor_log)): ?>
            <h3><?php _e('Recent Activity', 'ultimate-wp-backup-migration'); ?></h3>
            <div class="uwpbm-monitor-log">
                <?php foreach (array_slice($monitor_log, 0, 5) as $entry): ?>
                    <div class="log-entry">
                        <span class="timestamp"><?php echo esc_html($entry['timestamp']); ?></span>
                        <span class="changes">
                            <?php if (!empty($entry['changes']['database'])): ?>
                                <?php printf(__('%d database tables changed', 'ultimate-wp-backup-migration'), count($entry['changes']['database'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($entry['changes']['files'])): ?>
                                <?php printf(__('%d files changed', 'ultimate-wp-backup-migration'), count($entry['changes']['files'])); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Scheduled Backups -->
    <div class="uwpbm-card">
        <h2><?php _e('Scheduled Backups', 'ultimate-wp-backup-migration'); ?></h2>
        
        <button class="button button-primary" id="add-schedule-btn"><?php _e('Add New Schedule', 'ultimate-wp-backup-migration'); ?></button>
        
        <?php if (!empty($schedules)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'ultimate-wp-backup-migration'); ?></th>
                        <th><?php _e('Frequency', 'ultimate-wp-backup-migration'); ?></th>
                        <th><?php _e('Time', 'ultimate-wp-backup-migration'); ?></th>
                        <th><?php _e('Protocol', 'ultimate-wp-backup-migration'); ?></th>
                        <th><?php _e('Status', 'ultimate-wp-backup-migration'); ?></th>
                        <th><?php _e('Actions', 'ultimate-wp-backup-migration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><strong><?php echo esc_html($schedule['name']); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($schedule['frequency'])); ?></td>
                            <td><?php echo esc_html($schedule['time']); ?></td>
                            <td><?php echo esc_html(strtoupper($schedule['protocol'])); ?></td>
                            <td>
                                <span class="uwpbm-status uwpbm-status-<?php echo $schedule['enabled'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $schedule['enabled'] ? __('Active', 'ultimate-wp-backup-migration') : __('Inactive', 'ultimate-wp-backup-migration'); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small uwpbm-delete-schedule" data-schedule-id="<?php echo esc_attr($schedule['id']); ?>">
                                    <?php _e('Delete', 'ultimate-wp-backup-migration'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No scheduled backups configured.', 'ultimate-wp-backup-migration'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Schedule Modal -->
<div id="add-schedule-modal" class="uwpbm-modal" style="display: none;">
    <div class="uwpbm-modal-content">
        <span class="uwpbm-modal-close">&times;</span>
        <h2><?php _e('Add New Schedule', 'ultimate-wp-backup-migration'); ?></h2>
        
        <form id="add-schedule-form">
            <div class="uwpbm-form-row">
                <label for="schedule_name"><?php _e('Schedule Name', 'ultimate-wp-backup-migration'); ?></label>
                <input type="text" id="schedule_name" name="name" required>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="schedule_frequency"><?php _e('Frequency', 'ultimate-wp-backup-migration'); ?></label>
                <select id="schedule_frequency" name="frequency" required>
                    <option value="daily"><?php _e('Daily', 'ultimate-wp-backup-migration'); ?></option>
                    <option value="weekly"><?php _e('Weekly', 'ultimate-wp-backup-migration'); ?></option>
                    <option value="monthly"><?php _e('Monthly', 'ultimate-wp-backup-migration'); ?></option>
                </select>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="schedule_time"><?php _e('Time', 'ultimate-wp-backup-migration'); ?></label>
                <input type="time" id="schedule_time" name="time" value="02:00" required>
            </div>
            
            <div class="uwpbm-form-row">
                <label for="schedule_protocol"><?php _e('Storage Protocol', 'ultimate-wp-backup-migration'); ?></label>
                <select id="schedule_protocol" name="protocol" required>
                    <option value="local"><?php _e('Local', 'ultimate-wp-backup-migration'); ?></option>
                    <option value="ftp"><?php _e('FTP', 'ultimate-wp-backup-migration'); ?></option>
                    <option value="sftp"><?php _e('SFTP', 'ultimate-wp-backup-migration'); ?></option>
                </select>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="incremental" value="1">
                    <?php _e('Incremental Backup', 'ultimate-wp-backup-migration'); ?>
                </label>
            </div>
            
            <div class="uwpbm-form-row">
                <label>
                    <input type="checkbox" name="email_notification" value="1">
                    <?php _e('Email Notifications', 'ultimate-wp-backup-migration'); ?>
                </label>
            </div>
            
            <div class="uwpbm-modal-actions">
                <button type="submit" class="button button-primary"><?php _e('Create Schedule', 'ultimate-wp-backup-migration'); ?></button>
                <button type="button" class="button uwpbm-modal-close"><?php _e('Cancel', 'ultimate-wp-backup-migration'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.uwpbm-toggle { display: flex; align-items: center; gap: 10px; }
.uwpbm-toggle-slider { width: 50px; height: 24px; background: #ccc; border-radius: 12px; position: relative; cursor: pointer; }
.uwpbm-toggle-slider:before { content: ''; position: absolute; width: 20px; height: 20px; background: white; border-radius: 50%; top: 2px; left: 2px; transition: 0.3s; }
.uwpbm-toggle input:checked + .uwpbm-toggle-slider { background: #0073aa; }
.uwpbm-toggle input:checked + .uwpbm-toggle-slider:before { transform: translateX(26px); }
.uwpbm-toggle input { display: none; }

.uwpbm-monitor-log { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
.log-entry { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
.log-entry:last-child { border-bottom: none; }

.uwpbm-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; }
.uwpbm-modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; }
.uwpbm-modal-close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; }
.uwpbm-modal-actions { margin-top: 20px; text-align: right; }
.uwpbm-modal-actions .button { margin-left: 10px; }

.uwpbm-status-active { background: #d4edda; color: #155724; }
.uwpbm-status-inactive { background: #f8d7da; color: #721c24; }
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle monitoring
    $('#monitor-toggle').on('change', function() {
        $.post(ajaxurl, {
            action: 'uwpbm_toggle_monitor',
            nonce: '<?php echo wp_create_nonce('uwpbm_ajax'); ?>',
            enabled: this.checked ? 1 : 0
        });
    });
    
    // Show add schedule modal
    $('#add-schedule-btn').on('click', function() {
        $('#add-schedule-modal').show();
    });
    
    // Hide modal
    $('.uwpbm-modal-close').on('click', function() {
        $('#add-schedule-modal').hide();
    });
    
    // Add schedule
    $('#add-schedule-form').on('submit', function(e) {
        e.preventDefault();
        
        $.post(ajaxurl, {
            action: 'uwpbm_create_schedule',
            nonce: '<?php echo wp_create_nonce('uwpbm_ajax'); ?>',
            ...Object.fromEntries(new FormData(this))
        }).done(function() {
            location.reload();
        });
    });
    
    // Delete schedule
    $('.uwpbm-delete-schedule').on('click', function() {
        if (confirm('Are you sure?')) {
            $.post(ajaxurl, {
                action: 'uwpbm_delete_schedule',
                nonce: '<?php echo wp_create_nonce('uwpbm_ajax'); ?>',
                schedule_id: $(this).data('schedule-id')
            }).done(function() {
                location.reload();
            });
        }
    });
});
</script>