<?php
/**
 * Setup wizard
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_Wizard {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_wizard_page']);
        add_action('wp_ajax_uwpbm_wizard_step', [$this, 'handle_wizard_step']);
    }
    
    public function add_wizard_page() {
        if (get_option('uwpbm_wizard_completed')) {
            return;
        }
        
        add_dashboard_page(
            __('Setup Wizard', 'ultimate-wp-backup-migration'),
            __('Setup Wizard', 'ultimate-wp-backup-migration'),
            'manage_options',
            'uwpbm-wizard',
            [$this, 'render_wizard']
        );
    }
    
    public function render_wizard() {
        ?>
        <div class="wrap uwpbm-wizard">
            <h1><?php _e('Ultimate Backup Migration - Setup Wizard', 'ultimate-wp-backup-migration'); ?></h1>
            
            <div class="uwpbm-wizard-container">
                <div class="uwpbm-wizard-steps">
                    <div class="uwpbm-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-title"><?php _e('Welcome', 'ultimate-wp-backup-migration'); ?></span>
                    </div>
                    <div class="uwpbm-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-title"><?php _e('Storage', 'ultimate-wp-backup-migration'); ?></span>
                    </div>
                    <div class="uwpbm-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-title"><?php _e('Schedule', 'ultimate-wp-backup-migration'); ?></span>
                    </div>
                    <div class="uwpbm-step" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-title"><?php _e('Complete', 'ultimate-wp-backup-migration'); ?></span>
                    </div>
                </div>
                
                <div class="uwpbm-wizard-content">
                    <!-- Step 1: Welcome -->
                    <div class="uwpbm-wizard-step" id="step-1">
                        <h2><?php _e('Welcome to Ultimate Backup Migration', 'ultimate-wp-backup-migration'); ?></h2>
                        <p><?php _e('This wizard will help you set up your backup system in just a few steps.', 'ultimate-wp-backup-migration'); ?></p>
                        
                        <div class="uwpbm-features">
                            <div class="feature">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Unlimited file sizes', 'ultimate-wp-backup-migration'); ?>
                            </div>
                            <div class="feature">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Multiple storage protocols', 'ultimate-wp-backup-migration'); ?>
                            </div>
                            <div class="feature">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Complete local control', 'ultimate-wp-backup-migration'); ?>
                            </div>
                            <div class="feature">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Automated scheduling', 'ultimate-wp-backup-migration'); ?>
                            </div>
                        </div>
                        
                        <button class="button button-primary uwpbm-next-step"><?php _e('Get Started', 'ultimate-wp-backup-migration'); ?></button>
                    </div>
                    
                    <!-- Step 2: Storage Configuration -->
                    <div class="uwpbm-wizard-step" id="step-2" style="display: none;">
                        <h2><?php _e('Configure Storage', 'ultimate-wp-backup-migration'); ?></h2>
                        <p><?php _e('Choose where to store your backups.', 'ultimate-wp-backup-migration'); ?></p>
                        
                        <div class="uwpbm-storage-options">
                            <label class="uwpbm-storage-option">
                                <input type="radio" name="storage_type" value="local" checked>
                                <div class="option-content">
                                    <h3><?php _e('Local Storage', 'ultimate-wp-backup-migration'); ?></h3>
                                    <p><?php _e('Store backups on your server', 'ultimate-wp-backup-migration'); ?></p>
                                </div>
                            </label>
                            
                            <label class="uwpbm-storage-option">
                                <input type="radio" name="storage_type" value="ftp">
                                <div class="option-content">
                                    <h3><?php _e('FTP Server', 'ultimate-wp-backup-migration'); ?></h3>
                                    <p><?php _e('Store backups on FTP server', 'ultimate-wp-backup-migration'); ?></p>
                                </div>
                            </label>
                            
                            <label class="uwpbm-storage-option">
                                <input type="radio" name="storage_type" value="sftp">
                                <div class="option-content">
                                    <h3><?php _e('SFTP Server', 'ultimate-wp-backup-migration'); ?></h3>
                                    <p><?php _e('Store backups on SFTP server', 'ultimate-wp-backup-migration'); ?></p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="uwpbm-wizard-actions">
                            <button class="button uwpbm-prev-step"><?php _e('Previous', 'ultimate-wp-backup-migration'); ?></button>
                            <button class="button button-primary uwpbm-next-step"><?php _e('Next', 'ultimate-wp-backup-migration'); ?></button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Schedule Configuration -->
                    <div class="uwpbm-wizard-step" id="step-3" style="display: none;">
                        <h2><?php _e('Setup Schedule', 'ultimate-wp-backup-migration'); ?></h2>
                        <p><?php _e('Configure automatic backups (optional).', 'ultimate-wp-backup-migration'); ?></p>
                        
                        <div class="uwpbm-form-row">
                            <label>
                                <input type="checkbox" id="enable_schedule" name="enable_schedule" value="1">
                                <?php _e('Enable automatic backups', 'ultimate-wp-backup-migration'); ?>
                            </label>
                        </div>
                        
                        <div id="schedule-options" style="display: none;">
                            <div class="uwpbm-form-row">
                                <label for="schedule_frequency"><?php _e('Frequency', 'ultimate-wp-backup-migration'); ?></label>
                                <select id="schedule_frequency" name="schedule_frequency">
                                    <option value="daily"><?php _e('Daily', 'ultimate-wp-backup-migration'); ?></option>
                                    <option value="weekly"><?php _e('Weekly', 'ultimate-wp-backup-migration'); ?></option>
                                    <option value="monthly"><?php _e('Monthly', 'ultimate-wp-backup-migration'); ?></option>
                                </select>
                            </div>
                            
                            <div class="uwpbm-form-row">
                                <label for="schedule_time"><?php _e('Time', 'ultimate-wp-backup-migration'); ?></label>
                                <input type="time" id="schedule_time" name="schedule_time" value="02:00">
                            </div>
                        </div>
                        
                        <div class="uwpbm-wizard-actions">
                            <button class="button uwpbm-prev-step"><?php _e('Previous', 'ultimate-wp-backup-migration'); ?></button>
                            <button class="button button-primary uwpbm-next-step"><?php _e('Next', 'ultimate-wp-backup-migration'); ?></button>
                        </div>
                    </div>
                    
                    <!-- Step 4: Complete -->
                    <div class="uwpbm-wizard-step" id="step-4" style="display: none;">
                        <h2><?php _e('Setup Complete!', 'ultimate-wp-backup-migration'); ?></h2>
                        <p><?php _e('Your backup system is now configured and ready to use.', 'ultimate-wp-backup-migration'); ?></p>
                        
                        <div class="uwpbm-completion-actions">
                            <button class="button button-primary uwpbm-create-backup"><?php _e('Create First Backup', 'ultimate-wp-backup-migration'); ?></button>
                            <button class="button uwpbm-finish-wizard"><?php _e('Go to Dashboard', 'ultimate-wp-backup-migration'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .uwpbm-wizard-container { max-width: 800px; margin: 20px 0; }
        .uwpbm-wizard-steps { display: flex; margin-bottom: 30px; }
        .uwpbm-step { flex: 1; text-align: center; padding: 10px; border-bottom: 3px solid #ddd; }
        .uwpbm-step.active { border-bottom-color: #0073aa; }
        .uwpbm-step.completed { border-bottom-color: #00a32a; }
        .step-number { display: block; width: 30px; height: 30px; line-height: 30px; background: #ddd; border-radius: 50%; margin: 0 auto 5px; }
        .uwpbm-step.active .step-number { background: #0073aa; color: white; }
        .uwpbm-storage-options { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 20px 0; }
        .uwpbm-storage-option { display: block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; }
        .uwpbm-storage-option:has(input:checked) { border-color: #0073aa; background: #f0f6fc; }
        .uwpbm-features .feature { display: flex; align-items: center; margin: 10px 0; }
        .uwpbm-features .dashicons { color: #00a32a; margin-right: 10px; }
        .uwpbm-wizard-actions { margin-top: 30px; }
        .uwpbm-completion-actions { text-align: center; margin: 30px 0; }
        .uwpbm-completion-actions .button { margin: 0 10px; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let currentStep = 1;
            
            $('.uwpbm-next-step').on('click', function() {
                if (currentStep < 4) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
            
            $('.uwpbm-prev-step').on('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
            
            $('#enable_schedule').on('change', function() {
                $('#schedule-options').toggle(this.checked);
            });
            
            $('.uwpbm-finish-wizard').on('click', function() {
                $.post(ajaxurl, {
                    action: 'uwpbm_wizard_step',
                    step: 'complete',
                    nonce: '<?php echo wp_create_nonce('uwpbm_wizard'); ?>'
                }).done(function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=uwpbm-dashboard'); ?>';
                });
            });
            
            function showStep(step) {
                $('.uwpbm-wizard-step').hide();
                $('#step-' + step).show();
                
                $('.uwpbm-step').removeClass('active completed');
                for (let i = 1; i < step; i++) {
                    $('.uwpbm-step[data-step="' + i + '"]').addClass('completed');
                }
                $('.uwpbm-step[data-step="' + step + '"]').addClass('active');
            }
        });
        </script>
        <?php
    }
    
    public function handle_wizard_step() {
        check_ajax_referer('uwpbm_wizard', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ultimate-wp-backup-migration'));
        }
        
        $step = sanitize_text_field($_POST['step']);
        
        if ($step === 'complete') {
            update_option('uwpbm_wizard_completed', true);
            wp_send_json_success();
        }
        
        wp_send_json_error();
    }
}