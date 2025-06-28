/**
 * WP Kontext Gen Admin JavaScript
 */

(function($) {
    'use strict';

    let currentPredictionId = null;
    let statusCheckInterval = null;

    $(document).ready(function() {
        
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
            let statusText = 'Status: ' + data.status;
            
            if (data.status === 'processing' && data.logs) {
                // Show progress if available
                let logs = data.logs.split('\n');
                let lastLog = logs[logs.length - 1];
                if (lastLog) {
                    statusText += ' - ' + lastLog;
                }
            }
            
            $('#generation-status').html(statusText);
        }
        
        // Show result
        function showResult(data) {
            $('#generation-status').removeClass('notice-info notice-error').addClass('notice-success').html('Generation completed successfully!');
            
            let output = data.output;
            if (typeof output === 'string') {
                output = [output];
            }
            
            if (output && output.length > 0) {
                let html = '<img src="' + output[0] + '" alt="Generated image" />';
                html += '<div class="result-actions">';
                html += '<a href="' + output[0] + '" class="button button-primary" target="_blank">View Full Size</a>';
                html += '<button type="button" class="button" onclick="wpKontextGenDownload(\'' + output[0] + '\')">Download</button>';
                html += '</div>';
                
                $('#generation-result').html(html);
            }
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

})(jQuery);