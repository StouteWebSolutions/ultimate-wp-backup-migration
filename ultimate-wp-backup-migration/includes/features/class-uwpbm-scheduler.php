<?php
/**
 * Backup scheduler
 *
 * @package Ultimate_WP_Backup_Migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UWPBM_Scheduler {
    
    public function __construct() {
        add_action('uwpbm_scheduled_backup', [$this, 'run_scheduled_backup']);
        add_action('init', [$this, 'setup_schedules']);
    }
    
    public function setup_schedules() {
        $schedules = get_option('uwpbm_backup_schedules', []);
        
        foreach ($schedules as $schedule) {
            if ($schedule['enabled']) {
                $this->schedule_backup($schedule);
            }
        }
    }
    
    public function schedule_backup($schedule) {
        $hook = 'uwpbm_scheduled_backup_' . $schedule['id'];
        
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(
                $this->get_next_run_time($schedule),
                $schedule['frequency'],
                $hook,
                [$schedule]
            );
        }
    }
    
    public function unschedule_backup($schedule_id) {
        $hook = 'uwpbm_scheduled_backup_' . $schedule_id;
        wp_clear_scheduled_hook($hook);
    }
    
    public function run_scheduled_backup($schedule) {
        try {
            $options = [
                'name' => 'scheduled-' . date('Y-m-d-H-i-s'),
                'protocol' => $schedule['protocol'],
                'include_database' => $schedule['include_database'] ?? true,
                'include_media' => $schedule['include_media'] ?? true,
                'include_plugins' => $schedule['include_plugins'] ?? true,
                'include_themes' => $schedule['include_themes'] ?? true,
            ];
            
            if ($schedule['incremental']) {
                $incremental = new UWPBM_Incremental();
                $backup_id = $incremental->create_incremental_backup($options);
            } else {
                $migrator = new UWPBM_Migrator();
                $backup_id = $migrator->start_export($options);
            }
            
            // Send notification
            if ($schedule['email_notification']) {
                $this->send_notification($schedule, 'success', $backup_id);
            }
            
        } catch (Exception $e) {
            if ($schedule['email_notification']) {
                $this->send_notification($schedule, 'failed', null, $e->getMessage());
            }
        }
    }
    
    public function add_schedule($data) {
        $schedules = get_option('uwpbm_backup_schedules', []);
        
        $schedule = [
            'id' => uniqid(),
            'name' => sanitize_text_field($data['name']),
            'frequency' => sanitize_text_field($data['frequency']),
            'time' => sanitize_text_field($data['time']),
            'protocol' => sanitize_text_field($data['protocol']),
            'incremental' => !empty($data['incremental']),
            'include_database' => !empty($data['include_database']),
            'include_media' => !empty($data['include_media']),
            'include_plugins' => !empty($data['include_plugins']),
            'include_themes' => !empty($data['include_themes']),
            'email_notification' => !empty($data['email_notification']),
            'email_addresses' => sanitize_textarea_field($data['email_addresses'] ?? ''),
            'enabled' => true,
            'created' => current_time('mysql'),
        ];
        
        $schedules[] = $schedule;
        update_option('uwpbm_backup_schedules', $schedules);
        
        $this->schedule_backup($schedule);
        
        return $schedule['id'];
    }
    
    public function delete_schedule($schedule_id) {
        $schedules = get_option('uwpbm_backup_schedules', []);
        
        foreach ($schedules as $key => $schedule) {
            if ($schedule['id'] === $schedule_id) {
                $this->unschedule_backup($schedule_id);
                unset($schedules[$key]);
                break;
            }
        }
        
        update_option('uwpbm_backup_schedules', array_values($schedules));
    }
    
    public function get_schedules() {
        return get_option('uwpbm_backup_schedules', []);
    }
    
    private function get_next_run_time($schedule) {
        $time_parts = explode(':', $schedule['time']);
        $hour = intval($time_parts[0]);
        $minute = intval($time_parts[1] ?? 0);
        
        $next_run = strtotime("today {$hour}:{$minute}");
        
        if ($next_run <= time()) {
            $next_run = strtotime("tomorrow {$hour}:{$minute}");
        }
        
        return $next_run;
    }
    
    private function send_notification($schedule, $status, $backup_id = null, $error = null) {
        $emails = array_filter(array_map('trim', explode(',', $schedule['email_addresses'])));
        
        if (empty($emails)) {
            $emails = [get_option('admin_email')];
        }
        
        $site_name = get_bloginfo('name');
        $subject = sprintf('[%s] Backup %s', $site_name, ucfirst($status));
        
        if ($status === 'success') {
            $message = sprintf(
                "Scheduled backup '%s' completed successfully.\n\nBackup ID: %d\nTime: %s",
                $schedule['name'],
                $backup_id,
                current_time('mysql')
            );
        } else {
            $message = sprintf(
                "Scheduled backup '%s' failed.\n\nError: %s\nTime: %s",
                $schedule['name'],
                $error,
                current_time('mysql')
            );
        }
        
        foreach ($emails as $email) {
            wp_mail($email, $subject, $message);
        }
    }
}