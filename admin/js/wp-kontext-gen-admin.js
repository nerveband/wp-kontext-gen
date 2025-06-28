/**
 * WP Kontext Gen Admin JavaScript
 */

(function($) {
    'use strict';

    let currentPredictionId = null;
    let statusCheckInterval = null;

    $(document).ready(function() {
        
        // Toggle advanced options
        $('.advanced-options-toggle').on('click', function() {
            let content = $('#advanced-options-content');
            let icon = $('#advanced-toggle-icon');
            
            if (content.is(':visible')) {
                content.slideUp();
                icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
            } else {
                content.slideDown();
                icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
            }
        });
        
        // Media uploader for input image
        $('#upload_input_image').on('click', function(e) {
            e.preventDefault();
            
            let mediaUploader = wp.media({
                title: wpKontextGen.strings.select_image,
                button: {
                    text: wpKontextGen.strings.use_image
                },
                multiple: false,
                library: {
                    type: ['image']
                }
            });
            
            mediaUploader.on('select', function() {
                let attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#input_image').val(attachment.url);
                
                // Show preview
                let preview = '<img src="' + attachment.url + '" alt="" />';
                $('#input_image_preview').html(preview);
                
                // Show/hide buttons
                $('#upload_input_image').text('Change Image');
                $('#remove_input_image').show();
            });
            
            mediaUploader.open();
        });
        
        // Remove input image
        $('#remove_input_image').on('click', function(e) {
            e.preventDefault();
            $('#input_image').val('');
            $('#input_image_preview').empty();
            $('#upload_input_image').text(wpKontextGen.strings.select_image);
            $(this).hide();
        });
        
        // Media uploader for default image in settings
        $('#select_default_image').on('click', function(e) {
            e.preventDefault();
            
            let mediaUploader = wp.media({
                title: wpKontextGen.strings.select_image,
                button: {
                    text: wpKontextGen.strings.use_image
                },
                multiple: false,
                library: {
                    type: ['image']
                }
            });
            
            mediaUploader.on('select', function() {
                let attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#wp_kontext_gen_default_image').val(attachment.url);
            });
            
            mediaUploader.open();
        });
        
        // Clear prompt button
        $('.clear-prompt-btn').on('click', function() {
            $('#prompt').val('');
            $(this).hide();
        });
        
        // View changelog
        $('#view-changelog').on('click', function() {
            $('#changelog-modal').show();
            loadChangelog();
        });
        
        // Close changelog modal
        $('.changelog-close, #changelog-modal').on('click', function(e) {
            if (e.target === this) {
                $('#changelog-modal').hide();
            }
        });
        
        // Check for updates
        $('#check-updates').on('click', function() {
            let button = $(this);
            let statusDiv = $('#update-status');
            
            button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-alt');
            statusDiv.html('<div class="notice notice-info inline"><p>Checking for updates...</p></div>');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_check_updates',
                    nonce: wpKontextGen.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    
                    if (response.success) {
                        if (response.data.update_available) {
                            statusDiv.html(
                                '<div class="notice notice-warning inline"><p><strong>Update Available:</strong> Version ' + 
                                response.data.latest_version + ' is available. <a href="' + wpKontextGen.adminUrl + 'plugins.php">Update now</a></p></div>'
                            );
                        } else {
                            statusDiv.html('<div class="notice notice-success inline"><p>You have the latest version!</p></div>');
                        }
                    } else {
                        statusDiv.html('<div class="notice notice-error inline"><p>Unable to check for updates. Please try again later.</p></div>');
                    }
                },
                error: function() {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    statusDiv.html('<div class="notice notice-error inline"><p>Error checking for updates. Please try again later.</p></div>');
                }
            });
        });
        
        // Force WordPress update check
        $('#force-update-check').on('click', function() {
            let button = $(this);
            let statusDiv = $('#update-status');
            
            button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-alt');
            statusDiv.html('<div class="notice notice-info inline"><p>Forcing WordPress to check for plugin updates...</p></div>');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_force_update_check',
                    nonce: wpKontextGen.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    
                    if (response.success) {
                        if (response.data.update_available) {
                            statusDiv.html(
                                '<div class="notice notice-warning inline"><p><strong>Update Available:</strong> Version ' + 
                                response.data.latest_version + ' is available. <a href="' + response.data.update_url + '">Go to Plugins page to update</a></p></div>'
                            );
                        } else {
                            statusDiv.html('<div class="notice notice-success inline"><p>You have the latest version! WordPress update cache cleared.</p></div>');
                        }
                    } else {
                        statusDiv.html('<div class="notice notice-error inline"><p>Unable to force update check. Please try again later.</p></div>');
                    }
                },
                error: function() {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    statusDiv.html('<div class="notice notice-error inline"><p>Error forcing update check. Please try again later.</p></div>');
                }
            });
        });
        
        // Debug database
        $('#debug-database').on('click', function() {
            let button = $(this);
            let statusDiv = $('#debug-status');
            
            button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-alt');
            statusDiv.html('<div class="notice notice-info inline"><p>Checking database...</p></div>');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_debug_database',
                    nonce: wpKontextGen.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    
                    if (response.success) {
                        let debug = response.data.debug_info;
                        let html = '<div class="notice notice-info inline"><h4>Debug Information:</h4>';
                        html += '<p><strong>Table Name:</strong> ' + debug.table_name + '</p>';
                        html += '<p><strong>Table Exists:</strong> ' + debug.table_exists + '</p>';
                        
                        if (debug.record_count !== undefined) {
                            html += '<p><strong>Record Count:</strong> ' + debug.record_count + '</p>';
                        }
                        
                        if (debug.table_creation_attempted) {
                            html += '<p><strong>Table Creation:</strong> Attempted</p>';
                        }
                        
                        html += '<p><strong>WP Debug:</strong> ' + debug.wp_debug + '</p>';
                        html += '<p><strong>WP Debug Log:</strong> ' + debug.wp_debug_log + '</p>';
                        
                        if (debug.recent_records && debug.recent_records.length > 0) {
                            html += '<p><strong>Recent Records:</strong> ' + debug.recent_records.length + ' found</p>';
                        }
                        
                        if (debug.recent_logs && debug.recent_logs.length > 0) {
                            html += '<p><strong>Recent Debug Logs:</strong></p>';
                            html += '<div style="background: #f9f9f9; padding: 10px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">';
                            debug.recent_logs.forEach(function(log) {
                                html += log + '<br>';
                            });
                            html += '</div>';
                        }
                        
                        html += '</div>';
                        statusDiv.html(html);
                    } else {
                        statusDiv.html('<div class="notice notice-error inline"><p>Debug failed: ' + (response.data.message || 'Unknown error') + '</p></div>');
                    }
                },
                error: function() {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    statusDiv.html('<div class="notice notice-error inline"><p>Error running database debug.</p></div>');
                }
            });
        });
        
        // Test database insert
        $('#test-database-insert').on('click', function() {
            let button = $(this);
            let statusDiv = $('#debug-status');
            
            button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-alt');
            statusDiv.html('<div class="notice notice-info inline"><p>Testing database insert...</p></div>');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_test_database_insert',
                    nonce: wpKontextGen.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    
                    if (response.success) {
                        let html = '<div class="notice notice-success inline">';
                        html += '<p><strong>Test Insert Result:</strong> ' + response.data.test_records_created + ' test records created</p>';
                        html += '<p>' + response.data.message + '</p>';
                        html += '<p><em>Now click "Debug Database" to see detailed logs</em></p>';
                        html += '</div>';
                        statusDiv.html(html);
                    } else {
                        statusDiv.html('<div class="notice notice-error inline"><p>Test insert failed: ' + (response.data.message || 'Unknown error') + '</p></div>');
                    }
                },
                error: function() {
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt');
                    statusDiv.html('<div class="notice notice-error inline"><p>Error running test database insert.</p></div>');
                }
            });
        });
        
        // Delete history item
        $('.delete-history-item').on('click', function() {
            if (!confirm(wpKontextGen.strings.delete_confirm)) {
                return;
            }
            
            let button = $(this);
            let historyId = button.data('id');
            
            button.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_delete_image',
                    nonce: wpKontextGen.nonce,
                    history_id: historyId
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || wpKontextGen.strings.error);
                        button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert(wpKontextGen.strings.error);
                    button.prop('disabled', false).text('Delete');
                }
            });
        });
        
        // Clear history
        $('#clear_history_btn').on('click', function() {
            if (!confirm(wpKontextGen.strings.clear_history_confirm)) {
                return;
            }
            
            let button = $(this);
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_clear_history',
                    nonce: wpKontextGen.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || wpKontextGen.strings.error);
                        button.prop('disabled', false).text('Clear All History');
                    }
                },
                error: function() {
                    alert(wpKontextGen.strings.error);
                    button.prop('disabled', false).text('Clear All History');
                }
            });
        });
        
        // Save to media library (use event delegation for dynamically created buttons)
        $(document).on('click', '.save-to-media-btn', function() {
            let button = $(this);
            let imageUrl = button.data('url');
            let title = button.data('title');
            
            button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_save_to_media_library',
                    nonce: wpKontextGen.nonce,
                    image_url: imageUrl,
                    title: title
                },
                success: function(response) {
                    if (response.success) {
                        button.remove();
                        // Add Edit in WP button
                        let editButton = '<a href="' + wpKontextGen.adminUrl + 'post.php?post=' + response.data.attachment_id + '&action=edit" class="button button-small">Edit in WP</a>';
                        button.parent().append(editButton);
                        alert('Image saved to media library successfully!');
                    } else {
                        alert(response.data.message || wpKontextGen.strings.error);
                        button.prop('disabled', false).text('Save to Media');
                    }
                },
                error: function() {
                    alert(wpKontextGen.strings.error);
                    button.prop('disabled', false).text('Save to Media');
                }
            });
        });
        
        // Handle form submission
        $('#wp-kontext-gen-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate prompt
            let prompt = $('#prompt').val().trim();
            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }
            
            // Prepare data
            let formData = {
                action: 'wp_kontext_gen_generate',
                nonce: wpKontextGen.nonce,
                prompt: prompt
            };
            
            // Add optional fields
            let fields = [
                'input_image', 'aspect_ratio', 'num_inference_steps', 
                'guidance', 'seed', 'output_format', 'output_quality'
            ];
            
            fields.forEach(function(field) {
                let value = $('#' + field).val();
                if (value) {
                    formData[field] = value;
                }
            });
            
            // Add checkboxes
            if ($('#go_fast').is(':checked')) {
                formData.go_fast = 'true';
            }
            
            if ($('#disable_safety_checker').is(':checked')) {
                formData.disable_safety_checker = 'true';
            }
            
            // Show loading state
            $('#generate-btn').prop('disabled', true).text(wpKontextGen.strings.generating);
            $('#cancel-btn').show();
            $('#generation-status').removeClass('notice-error notice-success').addClass('notice-info').html('Starting generation...').show();
            $('#generation-result').empty();
            
            // Send request
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        currentPredictionId = response.data.id;
                        startStatusCheck();
                    } else {
                        showError(response.data.message || wpKontextGen.strings.error);
                        resetForm();
                    }
                },
                error: function() {
                    showError(wpKontextGen.strings.error);
                    resetForm();
                }
            });
        });
        
        // Cancel generation
        $('#cancel-btn').on('click', function() {
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
                statusCheckInterval = null;
            }
            resetForm();
            $('#generation-status').hide();
        });
        
        // Start checking prediction status
        function startStatusCheck() {
            let checkCount = 0;
            let maxChecks = 60; // 5 minutes max
            
            statusCheckInterval = setInterval(function() {
                checkCount++;
                
                if (checkCount > maxChecks) {
                    clearInterval(statusCheckInterval);
                    showError('Generation timed out');
                    resetForm();
                    return;
                }
                
                $.ajax({
                    url: wpKontextGen.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_kontext_gen_check_status',
                        nonce: wpKontextGen.nonce,
                        prediction_id: currentPredictionId
                    },
                    success: function(response) {
                        if (response.success) {
                            updateStatus(response.data);
                            
                            if (response.data.status === 'succeeded' || response.data.status === 'failed') {
                                clearInterval(statusCheckInterval);
                                statusCheckInterval = null;
                                
                                if (response.data.status === 'succeeded') {
                                    showResult(response.data);
                                } else {
                                    showError(response.data.error || 'Generation failed');
                                }
                                
                                resetForm();
                            }
                        } else {
                            clearInterval(statusCheckInterval);
                            showError(response.data.message || wpKontextGen.strings.error);
                            resetForm();
                        }
                    }
                });
            }, 5000); // Check every 5 seconds
        }
        
        // Update status display
        function updateStatus(data) {
            let statusText = '';
            
            switch (data.status) {
                case 'starting':
                    statusText = 'Starting generation...';
                    break;
                case 'processing':
                    statusText = 'Processing image...';
                    if (data.logs) {
                        let logs = data.logs.split('\n');
                        let lastLog = logs[logs.length - 1];
                        if (lastLog) {
                            statusText += ' - ' + lastLog;
                        }
                    }
                    break;
                case 'succeeded':
                    statusText = 'Generation completed successfully!';
                    break;
                case 'failed':
                    statusText = 'Generation failed';
                    if (data.error) {
                        statusText += ': ' + data.error;
                    }
                    break;
                default:
                    statusText = 'Status: ' + data.status;
                    break;
            }
            
            $('#generation-status').html(statusText);
        }
        
        // Show result
        function showResult(data) {
            let successMessage = 'Generation completed successfully! <a href="' + wpKontextGen.adminUrl + 'admin.php?page=wp-kontext-gen-history" class="button button-small button-secondary" style="margin-left: 10px;">View in History</a>';
            $('#generation-status').removeClass('notice-info notice-error').addClass('notice-success').html(successMessage);
            
            let output = data.output;
            if (typeof output === 'string') {
                output = [output];
            }
            
            if (output && output.length > 0) {
                let imageUrl = output[0];
                let html = '<img src="' + imageUrl + '" alt="Generated image" />';
                html += '<div class="result-actions">';
                html += '<a href="' + imageUrl + '" class="button button-primary" target="_blank">View Full Size</a>';
                html += '<button type="button" class="button" onclick="wpKontextGenDownload(\'' + imageUrl + '\')">Download</button>';
                
                // Add Save to Media Library button
                if (data.attachment_id) {
                    // Already saved to media library
                    html += '<a href="' + wpKontextGen.adminUrl + 'post.php?post=' + data.attachment_id + '&action=edit" class="button button-secondary">Edit in Media Library</a>';
                } else {
                    // Not saved yet, show save button
                    html += '<button type="button" class="button save-to-media-btn" data-url="' + imageUrl + '" data-title="Kontext Generated - ' + new Date().toISOString().slice(0, 19).replace('T', ' ') + '">Save to Media Library</button>';
                }
                
                html += '</div>';
                
                $('#generation-result').html(html);
            }
            
            // Refresh recent generations section
            refreshRecentGenerations();
        }
        
        // Show error
        function showError(message) {
            $('#generation-status').removeClass('notice-info notice-success').addClass('notice-error').html(message).show();
        }
        
        // Reset form state
        function resetForm() {
            $('#generate-btn').prop('disabled', false).text('Generate Image');
            $('#cancel-btn').hide();
        }
        
        // Refresh recent generations section
        function refreshRecentGenerations() {
            $.ajax({
                url: wpKontextGen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_kontext_gen_refresh_recent_generations',
                    nonce: wpKontextGen.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('#recent-generations-content').fadeOut(200, function() {
                            $(this).html(response.data.html).fadeIn(200);
                        });
                    }
                },
                error: function() {
                    // Silently fail - not critical
                    console.log('Failed to refresh recent generations');
                }
            });
        }
    });
    
    // Download helper function
    window.wpKontextGenDownload = function(url) {
        let link = document.createElement('a');
        link.href = url;
        link.download = 'kontext-generated-' + Date.now() + '.jpg';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
    
    // Load changelog from GitHub
    function loadChangelog() {
        let body = $('.changelog-body');
        body.html('<div class="loading">Loading changelog...</div>');
        
        fetch('https://api.github.com/repos/nerveband/wp-kontext-gen/releases')
            .then(response => response.json())
            .then(releases => {
                let html = '';
                releases.slice(0, 5).forEach(release => {
                    let version = release.tag_name;
                    let date = new Date(release.published_at).toLocaleDateString();
                    let body = release.body || 'No release notes available.';
                    
                    html += `
                        <div class="changelog-version">
                            <h4>Version ${version} <small>(${date})</small></h4>
                            <div class="changelog-notes">${body}</div>
                        </div>
                    `;
                });
                
                body.html(html);
            })
            .catch(error => {
                body.html('<div class="error">Failed to load changelog. Please check our <a href="https://github.com/nerveband/wp-kontext-gen/releases" target="_blank">GitHub releases</a> page.</div>');
            });
    }

})(jQuery);