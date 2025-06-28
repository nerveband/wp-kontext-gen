<?php
/**
 * Main admin page display
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Check if API key is configured
$api_key = get_option('wp_kontext_gen_api_key');
if (empty($api_key)) {
    ?>
    <div class="notice notice-warning">
        <p><?php _e('Please configure your Replicate API key in the settings page.', 'wp-kontext-gen'); ?></p>
        <p><a href="<?php echo admin_url('admin.php?page=wp-kontext-gen-settings'); ?>" class="button button-primary"><?php _e('Configure Settings', 'wp-kontext-gen'); ?></a></p>
    </div>
    <?php
}

// Get default parameters
$defaults = get_option('wp_kontext_gen_default_params', array());
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wp-kontext-gen-container">
        <div class="wp-kontext-gen-form-wrapper">
            <h2><?php _e('Generate or Edit Image', 'wp-kontext-gen'); ?></h2>
            
            <form id="wp-kontext-gen-form">
                <!-- Prompt -->
                <div class="form-group">
                    <label for="prompt"><?php _e('Prompt (Required)', 'wp-kontext-gen'); ?></label>
                    <textarea id="prompt" name="prompt" rows="4" class="large-text" placeholder="<?php _e('e.g., Change the car color to red, turn the headlights on', 'wp-kontext-gen'); ?>" required></textarea>
                    <p class="description"><?php _e('Text description of what you want to generate, or the instruction on how to edit the given image.', 'wp-kontext-gen'); ?></p>
                </div>
                
                <!-- Input Image -->
                <div class="form-group">
                    <label for="input_image"><?php _e('Input Image (Required for editing)', 'wp-kontext-gen'); ?></label>
                    <div class="image-upload-wrapper">
                        <input type="hidden" id="input_image" name="input_image" />
                        <div id="input_image_preview" class="image-preview"></div>
                        <button type="button" class="button" id="upload_input_image"><?php _e('Select Image', 'wp-kontext-gen'); ?></button>
                        <button type="button" class="button" id="remove_input_image" style="display:none;"><?php _e('Remove', 'wp-kontext-gen'); ?></button>
                    </div>
                    <p class="description"><?php _e('Image to use as reference for editing. Must be jpeg, png, gif, or webp.', 'wp-kontext-gen'); ?></p>
                </div>
                
                <!-- Advanced Options -->
                <div class="form-group">
                    <h3><?php _e('Advanced Options', 'wp-kontext-gen'); ?></h3>
                    
                    <!-- Aspect Ratio -->
                    <div class="form-field">
                        <label for="aspect_ratio"><?php _e('Aspect Ratio', 'wp-kontext-gen'); ?></label>
                        <select id="aspect_ratio" name="aspect_ratio">
                            <option value="">Default</option>
                            <option value="match_input_image"><?php _e('Match Input Image', 'wp-kontext-gen'); ?></option>
                            <option value="1:1">1:1 (Square)</option>
                            <option value="16:9">16:9 (Landscape)</option>
                            <option value="9:16">9:16 (Portrait)</option>
                            <option value="4:3">4:3</option>
                            <option value="3:4">3:4</option>
                        </select>
                    </div>
                    
                    <!-- Inference Steps -->
                    <div class="form-field">
                        <label for="num_inference_steps"><?php _e('Inference Steps', 'wp-kontext-gen'); ?></label>
                        <input type="number" id="num_inference_steps" name="num_inference_steps" min="1" max="50" value="<?php echo isset($defaults['num_inference_steps']) ? esc_attr($defaults['num_inference_steps']) : '30'; ?>" />
                        <p class="description"><?php _e('Number of denoising steps (1-50)', 'wp-kontext-gen'); ?></p>
                    </div>
                    
                    <!-- Guidance Scale -->
                    <div class="form-field">
                        <label for="guidance"><?php _e('Guidance Scale', 'wp-kontext-gen'); ?></label>
                        <input type="number" id="guidance" name="guidance" min="0" max="10" step="0.1" value="<?php echo isset($defaults['guidance']) ? esc_attr($defaults['guidance']) : '2.5'; ?>" />
                        <p class="description"><?php _e('How closely to follow the prompt (0-10)', 'wp-kontext-gen'); ?></p>
                    </div>
                    
                    <!-- Seed -->
                    <div class="form-field">
                        <label for="seed"><?php _e('Seed', 'wp-kontext-gen'); ?></label>
                        <input type="number" id="seed" name="seed" placeholder="<?php _e('Random', 'wp-kontext-gen'); ?>" />
                        <p class="description"><?php _e('Random seed for reproducible generation', 'wp-kontext-gen'); ?></p>
                    </div>
                    
                    <!-- Output Format -->
                    <div class="form-field">
                        <label for="output_format"><?php _e('Output Format', 'wp-kontext-gen'); ?></label>
                        <select id="output_format" name="output_format">
                            <option value="webp" <?php selected(isset($defaults['output_format']) ? $defaults['output_format'] : 'webp', 'webp'); ?>>WebP</option>
                            <option value="jpg" <?php selected(isset($defaults['output_format']) ? $defaults['output_format'] : '', 'jpg'); ?>>JPEG</option>
                            <option value="png" <?php selected(isset($defaults['output_format']) ? $defaults['output_format'] : '', 'png'); ?>>PNG</option>
                        </select>
                    </div>
                    
                    <!-- Output Quality -->
                    <div class="form-field">
                        <label for="output_quality"><?php _e('Output Quality', 'wp-kontext-gen'); ?></label>
                        <input type="number" id="output_quality" name="output_quality" min="0" max="100" value="<?php echo isset($defaults['output_quality']) ? esc_attr($defaults['output_quality']) : '80'; ?>" />
                        <p class="description"><?php _e('Quality for JPEG/WebP (0-100)', 'wp-kontext-gen'); ?></p>
                    </div>
                    
                    <!-- Go Fast -->
                    <div class="form-field">
                        <label>
                            <input type="checkbox" id="go_fast" name="go_fast" value="true" <?php checked(isset($defaults['go_fast']) ? $defaults['go_fast'] : false, true); ?> />
                            <?php _e('Go Fast Mode', 'wp-kontext-gen'); ?>
                        </label>
                        <p class="description"><?php _e('Faster generation, may slightly reduce quality for difficult prompts', 'wp-kontext-gen'); ?></p>
                    </div>
                    
                    <!-- Safety Checker -->
                    <div class="form-field">
                        <label>
                            <input type="checkbox" id="disable_safety_checker" name="disable_safety_checker" value="true" />
                            <?php _e('Disable Safety Checker', 'wp-kontext-gen'); ?>
                        </label>
                        <p class="description"><?php _e('Disable NSFW content filtering', 'wp-kontext-gen'); ?></p>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-group">
                    <button type="submit" class="button button-primary button-large" id="generate-btn">
                        <?php _e('Generate Image', 'wp-kontext-gen'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="cancel-btn" style="display:none;">
                        <?php _e('Cancel', 'wp-kontext-gen'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="wp-kontext-gen-results">
            <h2><?php _e('Results', 'wp-kontext-gen'); ?></h2>
            <div id="generation-status"></div>
            <div id="generation-result"></div>
        </div>
    </div>
</div>