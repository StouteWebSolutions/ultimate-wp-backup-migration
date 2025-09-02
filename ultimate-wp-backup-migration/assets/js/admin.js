/**
 * Admin JavaScript for Ultimate WordPress Backup Migration
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        UWPBM.init();
    });
    
    // Main plugin object
    window.UWPBM = {
        
        /**
         * Initialize plugin functionality
         */
        init: function() {
            this.bindEvents();
            this.initProgressTracking();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Connection test
            $(document).on('click', '.uwpbm-test-connection', this.testConnection);
            
            // Start backup
            $(document).on('click', '.uwpbm-start-backup', this.startBackup);
            
            // Delete backup
            $(document).on('click', '.uwpbm-delete-backup', this.deleteBackup);
            
            // Restore backup
            $(document).on('click', '.uwpbm-restore-backup', this.restoreBackup);
            
            // Form validation
            $(document).on('submit', '.uwpbm-form', this.validateForm);
        },
        
        /**
         * Test connection to storage provider
         */
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('form');
            var protocol = $form.find('[name="protocol"]').val();
            
            // Collect form data
            var settings = {};
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (name && name !== 'protocol') {
                    settings[name] = $field.val();
                }
            });
            
            // Show loading state
            $button.prop('disabled', true).html('<span class="uwpbm-spinner"></span> Testing...');
            
            // Clear previous results
            $('.uwpbm-connection-test').remove();
            
            // Make AJAX request
            $.post(uwpbm_ajax.url, {
                action: 'uwpbm_test_connection',
                nonce: uwpbm_ajax.nonce,
                protocol: protocol,
                settings: settings
            })
            .done(function(response) {
                if (response.success) {
                    UWPBM.showConnectionResult('success', response.data.message);
                } else {
                    UWPBM.showConnectionResult('error', response.data.message);
                }
            })
            .fail(function() {
                UWPBM.showConnectionResult('error', 'Connection test failed. Please try again.');
            })
            .always(function() {
                $button.prop('disabled', false).html('Test Connection');
            });
        },
        
        /**
         * Show connection test result
         */
        showConnectionResult: function(type, message) {
            var $result = $('<div class="uwpbm-connection-test ' + type + '">' + message + '</div>');
            $('.uwpbm-test-connection').after($result);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $result.fadeOut();
            }, 5000);
        },
        
        /**
         * Start backup process
         */
        startBackup: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('form');
            
            // Collect export options
            var options = {};
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (name) {
                    if ($field.is(':checkbox')) {
                        options[name] = $field.is(':checked');
                    } else {
                        options[name] = $field.val();
                    }
                }
            });
            
            // Show progress modal
            UWPBM.showProgressModal('Starting backup...');
            
            // Start backup
            $.post(uwpbm_ajax.url, {
                action: 'uwpbm_start_backup',
                nonce: uwpbm_ajax.nonce,
                options: options
            })
            .done(function(response) {
                if (response.success) {
                    UWPBM.trackBackupProgress(response.data.backup_id);
                } else {
                    UWPBM.hideProgressModal();
                    UWPBM.showNotice('error', response.data.message);
                }
            })
            .fail(function() {
                UWPBM.hideProgressModal();
                UWPBM.showNotice('error', 'Failed to start backup. Please try again.');
            });
        },
        
        /**
         * Track backup progress
         */
        trackBackupProgress: function(backupId) {
            var progressInterval = setInterval(function() {
                $.post(uwpbm_ajax.url, {
                    action: 'uwpbm_backup_progress',
                    nonce: uwpbm_ajax.nonce,
                    backup_id: backupId
                })
                .done(function(response) {
                    if (response.success) {
                        var data = response.data;
                        UWPBM.updateProgress(data.progress, data.message);
                        
                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            UWPBM.hideProgressModal();
                            UWPBM.showNotice('success', 'Backup completed successfully!');
                            location.reload(); // Refresh to show new backup
                        } else if (data.status === 'failed') {
                            clearInterval(progressInterval);
                            UWPBM.hideProgressModal();
                            UWPBM.showNotice('error', 'Backup failed: ' + data.message);
                        }
                    }
                })
                .fail(function() {
                    clearInterval(progressInterval);
                    UWPBM.hideProgressModal();
                    UWPBM.showNotice('error', 'Lost connection to backup process.');
                });
            }, 2000); // Check every 2 seconds
        },
        
        /**
         * Delete backup
         */
        deleteBackup: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            var backupId = $button.data('backup-id');
            
            $button.prop('disabled', true).html('Deleting...');
            
            $.post(uwpbm_ajax.url, {
                action: 'uwpbm_delete_backup',
                nonce: uwpbm_ajax.nonce,
                backup_id: backupId
            })
            .done(function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut();
                    UWPBM.showNotice('success', 'Backup deleted successfully.');
                } else {
                    UWPBM.showNotice('error', response.data.message);
                }
            })
            .fail(function() {
                UWPBM.showNotice('error', 'Failed to delete backup. Please try again.');
            })
            .always(function() {
                $button.prop('disabled', false).html('Delete');
            });
        },
        
        /**
         * Restore backup
         */
        restoreBackup: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to restore this backup? This will overwrite your current site.')) {
                return;
            }
            
            var $button = $(this);
            var backupId = $button.data('backup-id');
            
            UWPBM.showProgressModal('Starting restore...');
            
            $.post(uwpbm_ajax.url, {
                action: 'uwpbm_restore_backup',
                nonce: uwpbm_ajax.nonce,
                backup_id: backupId
            })
            .done(function(response) {
                if (response.success) {
                    UWPBM.trackRestoreProgress(response.data.restore_id);
                } else {
                    UWPBM.hideProgressModal();
                    UWPBM.showNotice('error', response.data.message);
                }
            })
            .fail(function() {
                UWPBM.hideProgressModal();
                UWPBM.showNotice('error', 'Failed to start restore. Please try again.');
            });
        },
        
        /**
         * Track restore progress
         */
        trackRestoreProgress: function(restoreId) {
            var progressInterval = setInterval(function() {
                $.post(uwpbm_ajax.url, {
                    action: 'uwpbm_restore_progress',
                    nonce: uwpbm_ajax.nonce,
                    restore_id: restoreId
                })
                .done(function(response) {
                    if (response.success) {
                        var data = response.data;
                        UWPBM.updateProgress(data.progress, data.message);
                        
                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            UWPBM.hideProgressModal();
                            UWPBM.showNotice('success', 'Restore completed successfully!');
                        } else if (data.status === 'failed') {
                            clearInterval(progressInterval);
                            UWPBM.hideProgressModal();
                            UWPBM.showNotice('error', 'Restore failed: ' + data.message);
                        }
                    }
                })
                .fail(function() {
                    clearInterval(progressInterval);
                    UWPBM.hideProgressModal();
                    UWPBM.showNotice('error', 'Lost connection to restore process.');
                });
            }, 2000);
        },
        
        /**
         * Show progress modal
         */
        showProgressModal: function(message) {
            var modal = '<div class="uwpbm-progress-modal">' +
                       '<div class="uwpbm-progress-content">' +
                       '<h3>Processing...</h3>' +
                       '<div class="uwpbm-progress-bar-large">' +
                       '<div class="uwpbm-progress-fill-large" style="width: 0%"></div>' +
                       '</div>' +
                       '<div class="uwpbm-progress-text">' + message + '</div>' +
                       '</div>' +
                       '</div>';
            
            $('body').append(modal);
            $('.uwpbm-progress-modal').fadeIn();
        },
        
        /**
         * Update progress
         */
        updateProgress: function(percentage, message) {
            $('.uwpbm-progress-fill-large').css('width', percentage + '%');
            $('.uwpbm-progress-text').text(message);
        },
        
        /**
         * Hide progress modal
         */
        hideProgressModal: function() {
            $('.uwpbm-progress-modal').fadeOut(function() {
                $(this).remove();
            });
        },
        
        /**
         * Show notification
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="uwpbm-notice ' + type + '">' + message + '</div>');
            $('.wrap h1').after($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        },
        
        /**
         * Validate form before submission
         */
        validateForm: function(e) {
            var $form = $(this);
            var isValid = true;
            
            // Remove previous error messages
            $form.find('.uwpbm-error').remove();
            
            // Check required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    isValid = false;
                    $field.after('<div class="uwpbm-error">This field is required.</div>');
                }
            });
            
            // Check email fields
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val().trim();
                if (email && !UWPBM.isValidEmail(email)) {
                    isValid = false;
                    $field.after('<div class="uwpbm-error">Please enter a valid email address.</div>');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                UWPBM.showNotice('error', 'Please correct the errors below.');
            }
        },
        
        /**
         * Validate email address
         */
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        /**
         * Initialize progress tracking for existing operations
         */
        initProgressTracking: function() {
            // Check if there are any running operations
            var runningBackups = $('.uwpbm-status-running');
            if (runningBackups.length > 0) {
                runningBackups.each(function() {
                    var $row = $(this).closest('tr');
                    var backupId = $row.find('.uwpbm-restore-backup').data('backup-id');
                    if (backupId) {
                        UWPBM.trackBackupProgress(backupId);
                    }
                });
            }
        }
    };
    
})(jQuery);