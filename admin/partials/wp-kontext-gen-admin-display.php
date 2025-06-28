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

// Get default/last image
$default_image = '';
$remember_last = get_option('wp_kontext_gen_remember_last_image', 1); // Default to enabled
if ($remember_last) {
    $default_image = get_option('wp_kontext_gen_last_image', '');
}
if (empty($default_image)) {
    $default_image = get_option('wp_kontext_gen_default_image', '');
}

// Get current model and last prompt
$current_model = get_option('wp_kontext_gen_model', 'dev');
$last_prompt = get_option('wp_kontext_gen_last_prompt', '');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="current-model-info">
        <p><strong><?php _e('Current Model:', 'wp-kontext-gen'); ?></strong> 
        <?php
        switch ($current_model) {
            case 'pro':
                echo 'FLUX.1 Kontext [pro]';
                break;
            case 'max':
                echo 'FLUX.1 Kontext [max]';
                break;
            default:
                echo 'FLUX.1 Kontext [dev]';
                break;
        }
        ?>
        </p>
    </div>
    
    <div class="wp-kontext-gen-container">
        <div class="wp-kontext-gen-main-content">
            <div class="wp-kontext-gen-form-wrapper">
                <h2><?php _e('Generate or Edit Image', 'wp-kontext-gen'); ?></h2>
            
            <form id="wp-kontext-gen-form">
                <!-- Prompt -->
                <div class="form-group">
                    <label for="prompt"><?php _e('Prompt (Required)', 'wp-kontext-gen'); ?></label>
                    <div class="prompt-wrapper">
                        <textarea id="prompt" name="prompt" rows="4" class="large-text" placeholder="<?php _e('e.g., Change the car color to red, turn the headlights on', 'wp-kontext-gen'); ?>" required><?php echo esc_textarea($last_prompt); ?></textarea>
                        <?php if (!empty($last_prompt)) : ?>
                            <button type="button" class="button button-small clear-prompt-btn" style="margin-top: 5px;">
                                <?php _e('Clear Prompt', 'wp-kontext-gen'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="prompt-tips">
                        <h4><?php _e('💡 Prompting Tips:', 'wp-kontext-gen'); ?></h4>
                        <ul>
                            <li><strong><?php _e('Be Specific:', 'wp-kontext-gen'); ?></strong> <?php _e('Use clear, detailed language with exact colors and descriptions', 'wp-kontext-gen'); ?></li>
                            <li><strong><?php _e('Preserve Intentionally:', 'wp-kontext-gen'); ?></strong> <?php _e('Specify what should stay the same: "while keeping the same facial features"', 'wp-kontext-gen'); ?></li>
                            <li><strong><?php _e('Text Editing:', 'wp-kontext-gen'); ?></strong> <?php _e('Use quotation marks: "replace \'old text\' with \'new text\'"', 'wp-kontext-gen'); ?></li>
                            <li><strong><?php _e('Style Transfer:', 'wp-kontext-gen'); ?></strong> <?php _e('Be specific about artistic styles: "impressionist painting" not "artistic"', 'wp-kontext-gen'); ?></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Input Image -->
                <div class="form-group">
                    <label for="input_image"><?php _e('Input Image (Required for editing)', 'wp-kontext-gen'); ?></label>
                    <div class="image-upload-wrapper">
                        <input type="hidden" id="input_image" name="input_image" value="<?php echo esc_attr($default_image); ?>" />
                        <div id="input_image_preview" class="image-preview">
                            <?php if (!empty($default_image)) : ?>
                                <img src="<?php echo esc_url($default_image); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button" id="upload_input_image">
                            <?php echo !empty($default_image) ? __('Change Image', 'wp-kontext-gen') : __('Select Image', 'wp-kontext-gen'); ?>
                        </button>
                        <button type="button" class="button" id="remove_input_image" <?php echo empty($default_image) ? 'style="display:none;"' : ''; ?>>
                            <?php _e('Remove', 'wp-kontext-gen'); ?>
                        </button>
                    </div>
                    <p class="description"><?php _e('Image to use as reference for editing. Must be jpeg, png, gif, or webp.', 'wp-kontext-gen'); ?></p>
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
                
                <!-- Advanced Options -->
                <div class="form-group">
                    <h3 class="advanced-options-toggle" style="cursor: pointer;">
                        <span class="dashicons dashicons-arrow-right" id="advanced-toggle-icon"></span>
                        <?php _e('Advanced Options', 'wp-kontext-gen'); ?>
                    </h3>
                    <div id="advanced-options-content" style="display: none;">
                    
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
                    </div> <!-- Close advanced-options-content -->
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
        
        <!-- Recent Generations Sidebar -->
        <div class="wp-kontext-gen-sidebar">
            <div class="wp-kontext-gen-recent">
            <div class="recent-header">
                <h2><?php _e('Recent Generations', 'wp-kontext-gen'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=wp-kontext-gen-history'); ?>" class="button button-secondary">
                    <?php _e('View All History', 'wp-kontext-gen'); ?>
                </a>
            </div>
            <div id="recent-generations-content">
                <?php
                // Load recent generations
                global $wpdb;
                $table_name = $wpdb->prefix . 'kontext_gen_history';
                $recent_items = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 3",
                    get_current_user_id()
                ));
                
                if (!empty($recent_items)) {
                    echo '<div class="recent-generations-grid">';
                    foreach ($recent_items as $item) {
                        $parameters = json_decode($item->parameters, true);
                        ?>
                        <div class="recent-item">
                            <div class="recent-item-image">
                                <?php if ($item->output_image_url) : ?>
                                    <a href="<?php echo esc_url($item->output_image_url); ?>" target="_blank">
                                        <img src="<?php echo esc_url($item->output_image_url); ?>" alt="Generated image" />
                                    </a>
                                    <div class="image-status success"><?php _e('✓ Generated', 'wp-kontext-gen'); ?></div>
                                <?php elseif ($item->input_image_url) : ?>
                                    <img src="<?php echo esc_url($item->input_image_url); ?>" alt="Input image" style="opacity: 0.6;" />
                                    <div class="image-status <?php echo $item->status; ?>"><?php echo ucfirst($item->status); ?></div>
                                <?php else : ?>
                                    <div class="no-image">
                                        <div class="no-image-icon">📷</div>
                                        <div class="image-status <?php echo $item->status; ?>"><?php echo ucfirst($item->status); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="recent-item-info">
                                <div class="recent-prompt"><?php echo esc_html(substr($item->prompt, 0, 80) . (strlen($item->prompt) > 80 ? '...' : '')); ?></div>
                                <div class="recent-meta">
                                    <span class="recent-date"><?php echo human_time_diff(strtotime($item->created_at), current_time('timestamp')); ?> ago</span>
                                    <?php if ($item->cost_usd > 0) : ?>
                                        <span class="recent-cost">$<?php echo number_format($item->cost_usd, 4); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    echo '</div>';
                } else {
                    echo '<div class="no-recent-generations">';
                    echo '<p>' . __('No generations yet. Create your first image above!', 'wp-kontext-gen') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Base responsive container */
.wrap {
    max-width: none !important;
}

.wp-kontext-gen-container {
    display: flex;
    gap: 20px;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
}

.wp-kontext-gen-main-content {
    flex: 1;
    min-width: 0; /* Prevents flex item from overflowing */
    max-width: calc(100% - 370px); /* Ensure it doesn't overflow */
}

.wp-kontext-gen-sidebar {
    flex: 0 0 300px; /* Smaller fixed width sidebar */
    min-width: 300px;
    max-width: 300px;
}

.wp-kontext-gen-recent {
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 0;
    position: sticky;
    top: 32px; /* Account for admin bar */
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}

.recent-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.recent-header h2 {
    margin: 0;
    font-size: 18px;
}

.recent-generations-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.recent-item {
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    background: #f9f9f9;
    transition: transform 0.2s ease;
}

.recent-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.recent-item-image {
    position: relative;
    height: 80px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.recent-item-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
    width: 100%;
    height: 100%;
}

.recent-item-image .no-image {
    text-align: center;
    color: #666;
}

.recent-item-image .no-image-icon {
    font-size: 32px;
    margin-bottom: 5px;
}

.image-status {
    position: absolute;
    bottom: 5px;
    right: 5px;
    padding: 2px 6px;
    font-size: 11px;
    border-radius: 3px;
    font-weight: 600;
    text-transform: uppercase;
}

.image-status.success {
    background: #d4edda;
    color: #155724;
}

.image-status.succeeded {
    background: #d4edda;
    color: #155724;
}

.image-status.failed {
    background: #f8d7da;
    color: #721c24;
}

.image-status.processing,
.image-status.starting {
    background: #fff3cd;
    color: #856404;
}

.recent-item-info {
    padding: 12px;
}

.recent-prompt {
    font-weight: 500;
    margin-bottom: 8px;
    line-height: 1.3;
    color: #333;
}

.recent-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #666;
}

.recent-cost {
    background: #e7f5ff;
    color: #0066cc;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 500;
}

.no-recent-generations {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.no-recent-generations p {
    margin: 0;
    font-size: 16px;
}

/* WordPress admin responsive adjustments */
@media screen and (max-width: 1400px) {
    .wp-kontext-gen-container {
        flex-direction: column;
        gap: 20px;
    }
    
    .wp-kontext-gen-main-content {
        max-width: 100%;
    }
    
    .wp-kontext-gen-sidebar {
        flex: none;
        min-width: auto;
        max-width: 100%;
        width: 100%;
    }
    
    .wp-kontext-gen-recent {
        position: static;
        max-height: none;
        margin: 0;
    }
    
    .recent-generations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .recent-item {
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    
    .recent-item-image {
        flex: 0 0 120px;
        height: 80px;
        margin-right: 15px;
    }
    
    .recent-item-info {
        flex: 1;
        padding: 12px 12px 12px 0;
    }
}

@media screen and (max-width: 900px) {
    .recent-generations-grid {
        grid-template-columns: 1fr;
    }
    
    .recent-item {
        flex-direction: column;
        align-items: stretch;
    }
    
    .recent-item-image {
        flex: none;
        height: 120px;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .recent-item-info {
        padding: 12px;
    }
}

@media screen and (max-width: 600px) {
    .wp-kontext-gen-container {
        gap: 15px;
    }
    
    .wp-kontext-gen-recent {
        padding: 15px;
    }
    
    .recent-header {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .recent-header h2 {
        margin-bottom: 0;
        font-size: 16px;
    }
}

/* Override for WordPress admin constraints */
@media screen and (max-width: 1600px) {
    .wp-kontext-gen-container {
        flex-direction: column !important;
    }
    
    .wp-kontext-gen-sidebar {
        min-width: auto !important;
        max-width: 100% !important;
    }
    
    .wp-kontext-gen-main-content {
        max-width: 100% !important;
    }
}

/* Ensure it works in WordPress admin mobile view */
@media screen and (max-width: 782px) {
    .wp-kontext-gen-container {
        padding: 0 10px;
    }
    
    .recent-item-image {
        height: 100px !important;
    }
}
</style>