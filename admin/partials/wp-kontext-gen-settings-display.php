<?php
/**
 * Settings page display
 */

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

// Save settings
if (isset($_POST['submit'])) {
    check_admin_referer('wp_kontext_gen_settings');
    
    // Save API key
    $api_key = sanitize_text_field($_POST['wp_kontext_gen_api_key']);
    update_option('wp_kontext_gen_api_key', $api_key);
    
    // Validate API key
    $api = new WP_Kontext_Gen_API();
    $is_valid = $api->validate_api_key($api_key);
    
    // Save default parameters
    $defaults = array();
    if (isset($_POST['wp_kontext_gen_default_params'])) {
        $defaults = $_POST['wp_kontext_gen_default_params'];
    }
    update_option('wp_kontext_gen_default_params', $defaults);
    
    // Save default image
    $default_image = isset($_POST['wp_kontext_gen_default_image']) ? esc_url_raw($_POST['wp_kontext_gen_default_image']) : '';
    update_option('wp_kontext_gen_default_image', $default_image);
    
    // Save remember last image setting
    $remember_last = isset($_POST['wp_kontext_gen_remember_last_image']) ? 1 : 0;
    update_option('wp_kontext_gen_remember_last_image', $remember_last);
    
    // Save model selection
    $model = isset($_POST['wp_kontext_gen_model']) ? sanitize_text_field($_POST['wp_kontext_gen_model']) : 'dev';
    update_option('wp_kontext_gen_model', $model);
    
    if ($is_valid) {
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully. API key is valid!', 'wp-kontext-gen') . '</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p>' . __('Settings saved, but API key validation failed. Please check your key.', 'wp-kontext-gen') . '</p></div>';
    }
}

// Get current settings
$api_key = get_option('wp_kontext_gen_api_key', '');
$defaults = get_option('wp_kontext_gen_default_params', array());
$default_image = get_option('wp_kontext_gen_default_image', '');
$remember_last = get_option('wp_kontext_gen_remember_last_image', 1); // Default to enabled
$current_model = get_option('wp_kontext_gen_model', 'dev');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wp_kontext_gen_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wp_kontext_gen_api_key"><?php _e('Replicate API Key', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <input type="password" id="wp_kontext_gen_api_key" name="wp_kontext_gen_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('Get your API key from', 'wp-kontext-gen'); ?> 
                        <a href="https://replicate.com/account/api-tokens" target="_blank">Replicate Account</a>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wp_kontext_gen_model"><?php _e('FLUX.1 Kontext Model', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <select id="wp_kontext_gen_model" name="wp_kontext_gen_model">
                        <option value="dev" <?php selected($current_model, 'dev'); ?>><?php _e('FLUX.1 Kontext [dev] - Open-weight, non-commercial', 'wp-kontext-gen'); ?></option>
                        <option value="pro" <?php selected($current_model, 'pro'); ?>><?php _e('FLUX.1 Kontext [pro] - High-quality, commercial use', 'wp-kontext-gen'); ?></option>
                        <option value="max" <?php selected($current_model, 'max'); ?>><?php _e('FLUX.1 Kontext [max] - Premium, best performance', 'wp-kontext-gen'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Select which FLUX.1 Kontext model to use. Pro and Max models require paid plans.', 'wp-kontext-gen'); ?><br>
                        <strong><?php _e('Current Model:', 'wp-kontext-gen'); ?></strong> 
                        <span class="current-model-display">
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
                        </span>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Default Generation Parameters', 'wp-kontext-gen'); ?></h2>
        <p><?php _e('Set default values for image generation parameters.', 'wp-kontext-gen'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_num_inference_steps"><?php _e('Default Inference Steps', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <input type="number" id="default_num_inference_steps" name="wp_kontext_gen_default_params[num_inference_steps]" 
                           value="<?php echo isset($defaults['num_inference_steps']) ? esc_attr($defaults['num_inference_steps']) : '30'; ?>" 
                           min="1" max="50" />
                    <p class="description"><?php _e('Number of denoising steps (1-50). Default: 30', 'wp-kontext-gen'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="default_guidance"><?php _e('Default Guidance Scale', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <input type="number" id="default_guidance" name="wp_kontext_gen_default_params[guidance]" 
                           value="<?php echo isset($defaults['guidance']) ? esc_attr($defaults['guidance']) : '2.5'; ?>" 
                           min="0" max="10" step="0.1" />
                    <p class="description"><?php _e('How closely to follow the prompt (0-10). Default: 2.5', 'wp-kontext-gen'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="default_output_format"><?php _e('Default Output Format', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <select id="default_output_format" name="wp_kontext_gen_default_params[output_format]">
                        <option value="webp" <?php selected(isset($defaults['output_format']) ? $defaults['output_format'] : 'webp', 'webp'); ?>>WebP</option>
                        <option value="jpg" <?php selected(isset($defaults['output_format']) ? $defaults['output_format'] : '', 'jpg'); ?>>JPEG</option>
                        <option value="png" <?php selected(isset($defaults['output_format']) ? $defaults['output_format'] : '', 'png'); ?>>PNG</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="default_output_quality"><?php _e('Default Output Quality', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <input type="number" id="default_output_quality" name="wp_kontext_gen_default_params[output_quality]" 
                           value="<?php echo isset($defaults['output_quality']) ? esc_attr($defaults['output_quality']) : '80'; ?>" 
                           min="0" max="100" />
                    <p class="description"><?php _e('Quality for JPEG/WebP outputs (0-100). Default: 80', 'wp-kontext-gen'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php _e('Go Fast Mode', 'wp-kontext-gen'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="wp_kontext_gen_default_params[go_fast]" value="1" 
                               <?php checked(isset($defaults['go_fast']) ? $defaults['go_fast'] : false, true); ?> />
                        <?php _e('Enable fast mode by default', 'wp-kontext-gen'); ?>
                    </label>
                    <p class="description"><?php _e('Faster generation, may slightly reduce quality for difficult prompts', 'wp-kontext-gen'); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Image Settings', 'wp-kontext-gen'); ?></h2>
        <p><?php _e('Configure default and last used image settings.', 'wp-kontext-gen'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wp_kontext_gen_default_image"><?php _e('Default Input Image', 'wp-kontext-gen'); ?></label>
                </th>
                <td>
                    <input type="url" id="wp_kontext_gen_default_image" name="wp_kontext_gen_default_image" 
                           value="<?php echo esc_attr($default_image); ?>" class="regular-text" />
                    <button type="button" class="button" id="select_default_image"><?php _e('Select from Media Library', 'wp-kontext-gen'); ?></button>
                    <p class="description"><?php _e('Default image to use when generating. Users can still change this.', 'wp-kontext-gen'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php _e('Remember Last Image', 'wp-kontext-gen'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="wp_kontext_gen_remember_last_image" value="1" 
                               <?php checked($remember_last, 1); ?> />
                        <?php _e('Remember the last used input image', 'wp-kontext-gen'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the last used input image will be automatically loaded on the generation page.', 'wp-kontext-gen'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="wp-kontext-gen-attribution">
        <h2><?php _e('Plugin Information', 'wp-kontext-gen'); ?></h2>
        <div class="attribution-content">
            <div class="attribution-section">
                <h4><?php _e('Developer', 'wp-kontext-gen'); ?></h4>
                <p><?php _e('Created by', 'wp-kontext-gen'); ?> <strong>Ashraf Ali</strong></p>
                <p>
                    <a href="https://github.com/nerveband/wp-kontext-gen" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('View on GitHub', 'wp-kontext-gen'); ?>
                    </a>
                    <button type="button" class="button button-secondary" id="view-changelog">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('View Changelog', 'wp-kontext-gen'); ?>
                    </button>
                </p>
            </div>
            
            <div class="attribution-section">
                <h4><?php _e('Support & Issues', 'wp-kontext-gen'); ?></h4>
                <p><?php _e('Report bugs or request features on our GitHub repository.', 'wp-kontext-gen'); ?></p>
                <p>
                    <a href="https://github.com/nerveband/wp-kontext-gen/issues" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-sos"></span>
                        <?php _e('Report Issue', 'wp-kontext-gen'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <div class="attribution-content" style="margin-top: 20px;">
            <div class="attribution-section">
                <h4><?php _e('Plugin Updates', 'wp-kontext-gen'); ?></h4>
                <p><strong><?php _e('Current Version:', 'wp-kontext-gen'); ?></strong> v<?php echo WP_KONTEXT_GEN_VERSION; ?></p>
                <div id="update-status"></div>
                <p>
                    <button type="button" class="button button-secondary" id="check-updates">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Check for Updates', 'wp-kontext-gen'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="force-update-check">
                        <span class="dashicons dashicons-update-alt"></span>
                        <?php _e('Force WordPress Update Check', 'wp-kontext-gen'); ?>
                    </button>
                    <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php _e('Manage Plugins', 'wp-kontext-gen'); ?>
                    </a>
                </p>
                <p class="description">
                    <?php _e('Updates are automatically detected. You can also manually check for updates or manage the plugin in the WordPress Plugins page.', 'wp-kontext-gen'); ?>
                </p>
            </div>
            
            <div class="attribution-section">
                <h4><?php _e('Database Debug', 'wp-kontext-gen'); ?></h4>
                <p><?php _e('If history is not working properly, use this tool to check database status.', 'wp-kontext-gen'); ?></p>
                <div id="debug-status"></div>
                <p>
                    <button type="button" class="button button-secondary" id="debug-database">
                        <span class="dashicons dashicons-database"></span>
                        <?php _e('Debug Database', 'wp-kontext-gen'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="test-database-insert">
                        <span class="dashicons dashicons-database-add"></span>
                        <?php _e('Test Database Insert', 'wp-kontext-gen'); ?>
                    </button>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Changelog Modal -->
    <div id="changelog-modal" style="display: none;">
        <div class="changelog-content">
            <div class="changelog-header">
                <h3><?php _e('WP Kontext Gen Changelog', 'wp-kontext-gen'); ?></h3>
                <button type="button" class="changelog-close">&times;</button>
            </div>
            <div class="changelog-body">
                <div class="loading"><?php _e('Loading changelog...', 'wp-kontext-gen'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="wp-kontext-gen-info">
        <h2><?php _e('About FLUX.1 Kontext [dev]', 'wp-kontext-gen'); ?></h2>
        <p><?php _e('FLUX.1 Kontext is a state-of-the-art image editing model from Black Forest Labs that allows you to edit images using text prompts.', 'wp-kontext-gen'); ?></p>
        
        <h3><?php _e('What You Can Do:', 'wp-kontext-gen'); ?></h3>
        <ul>
            <li><strong><?php _e('Style Transfer', 'wp-kontext-gen'); ?>:</strong> <?php _e('Convert photos to different art styles (watercolor, oil painting, sketches)', 'wp-kontext-gen'); ?></li>
            <li><strong><?php _e('Object/Clothing Changes', 'wp-kontext-gen'); ?>:</strong> <?php _e('Modify hairstyles, add accessories, change colors', 'wp-kontext-gen'); ?></li>
            <li><strong><?php _e('Text Editing', 'wp-kontext-gen'); ?>:</strong> <?php _e('Replace text in signs, posters, and labels', 'wp-kontext-gen'); ?></li>
            <li><strong><?php _e('Background Swapping', 'wp-kontext-gen'); ?>:</strong> <?php _e('Change environments while preserving subjects', 'wp-kontext-gen'); ?></li>
            <li><strong><?php _e('Character Consistency', 'wp-kontext-gen'); ?>:</strong> <?php _e('Maintain identity across multiple edits', 'wp-kontext-gen'); ?></li>
        </ul>
        
        <h3><?php _e('Prompting Tips:', 'wp-kontext-gen'); ?></h3>
        <ul>
            <li><?php _e('Be specific with colors, styles, and descriptions', 'wp-kontext-gen'); ?></li>
            <li><?php _e('Use quotation marks for exact text replacements', 'wp-kontext-gen'); ?></li>
            <li><?php _e('Specify what should stay the same when editing', 'wp-kontext-gen'); ?></li>
            <li><?php _e('Start simple and iterate on successful edits', 'wp-kontext-gen'); ?></li>
        </ul>
    </div>
</div>