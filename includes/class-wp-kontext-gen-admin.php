<?php
/**
 * Admin functionality
 */

class WP_Kontext_Gen_Admin {
    
    private $plugin_name;
    private $version;
    private $api;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = new WP_Kontext_Gen_API();
    }
    
    /**
     * Register the admin menu
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('WP Kontext Gen', 'wp-kontext-gen'),
            __('Kontext Gen', 'wp-kontext-gen'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page'),
            'dashicons-format-image',
            30
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Generate Images', 'wp-kontext-gen'),
            __('Generate', 'wp-kontext-gen'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('History', 'wp-kontext-gen'),
            __('History', 'wp-kontext-gen'),
            'manage_options',
            $this->plugin_name . '-history',
            array($this, 'display_history_page')
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Settings', 'wp-kontext-gen'),
            __('Settings', 'wp-kontext-gen'),
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Add settings link on plugin page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('Settings', 'wp-kontext-gen') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wp_kontext_gen_settings', 'wp_kontext_gen_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        register_setting('wp_kontext_gen_settings', 'wp_kontext_gen_default_params', array(
            'sanitize_callback' => array($this, 'sanitize_default_params'),
        ));
        
        register_setting('wp_kontext_gen_settings', 'wp_kontext_gen_default_image', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
        
        register_setting('wp_kontext_gen_settings', 'wp_kontext_gen_remember_last_image', array(
            'sanitize_callback' => 'absint',
        ));
        
        register_setting('wp_kontext_gen_settings', 'wp_kontext_gen_model', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }
    
    /**
     * Sanitize default parameters
     */
    public function sanitize_default_params($input) {
        $sanitized = array();
        
        if (isset($input['num_inference_steps'])) {
            $sanitized['num_inference_steps'] = intval($input['num_inference_steps']);
        }
        
        if (isset($input['guidance'])) {
            $sanitized['guidance'] = floatval($input['guidance']);
        }
        
        if (isset($input['output_format'])) {
            $sanitized['output_format'] = sanitize_text_field($input['output_format']);
        }
        
        if (isset($input['output_quality'])) {
            $sanitized['output_quality'] = intval($input['output_quality']);
        }
        
        if (isset($input['go_fast'])) {
            $sanitized['go_fast'] = (bool)$input['go_fast'];
        }
        
        return $sanitized;
    }
    
    /**
     * Display the main admin page
     */
    public function display_plugin_admin_page() {
        include_once WP_KONTEXT_GEN_PLUGIN_PATH . 'admin/partials/wp-kontext-gen-admin-display.php';
    }
    
    /**
     * Display the history page
     */
    public function display_history_page() {
        include_once WP_KONTEXT_GEN_PLUGIN_PATH . 'admin/partials/wp-kontext-gen-history-display.php';
    }
    
    /**
     * Display the settings page
     */
    public function display_settings_page() {
        include_once WP_KONTEXT_GEN_PLUGIN_PATH . 'admin/partials/wp-kontext-gen-settings-display.php';
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, $this->plugin_name) === false) {
            return;
        }
        
        wp_enqueue_style($this->plugin_name, WP_KONTEXT_GEN_PLUGIN_URL . 'admin/css/wp-kontext-gen-admin.css', array(), $this->version, 'all');
        wp_enqueue_style('wp-color-picker');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, $this->plugin_name) === false) {
            return;
        }
        
        wp_enqueue_script($this->plugin_name, WP_KONTEXT_GEN_PLUGIN_URL . 'admin/js/wp-kontext-gen-admin.js', array('jquery', 'wp-color-picker'), $this->version, false);
        wp_enqueue_media();
        
        wp_localize_script($this->plugin_name, 'wpKontextGen', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce' => wp_create_nonce('wp_kontext_gen_nonce'),
            'strings' => array(
                'generating' => __('Generating...', 'wp-kontext-gen'),
                'error' => __('An error occurred', 'wp-kontext-gen'),
                'select_image' => __('Select Input Image', 'wp-kontext-gen'),
                'use_image' => __('Use This Image', 'wp-kontext-gen'),
                'delete_confirm' => __('Are you sure you want to delete this image?', 'wp-kontext-gen'),
                'clear_history_confirm' => __('Are you sure you want to clear all history? This cannot be undone.', 'wp-kontext-gen'),
            )
        ));
    }
    
    /**
     * Handle generate request via AJAX
     */
    public function handle_generate_request() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        $params = array();
        
        // Required parameter
        if (empty($_POST['prompt'])) {
            wp_send_json_error(array('message' => __('Prompt is required', 'wp-kontext-gen')));
        }
        $params['prompt'] = sanitize_text_field($_POST['prompt']);
        
        // Optional parameters
        if (!empty($_POST['input_image'])) {
            $params['input_image'] = esc_url_raw($_POST['input_image']);
        }
        
        if (!empty($_POST['aspect_ratio'])) {
            $params['aspect_ratio'] = sanitize_text_field($_POST['aspect_ratio']);
        }
        
        if (isset($_POST['num_inference_steps'])) {
            $params['num_inference_steps'] = intval($_POST['num_inference_steps']);
        }
        
        if (isset($_POST['guidance'])) {
            $params['guidance'] = floatval($_POST['guidance']);
        }
        
        if (isset($_POST['seed']) && $_POST['seed'] !== '') {
            $params['seed'] = intval($_POST['seed']);
        }
        
        if (!empty($_POST['output_format'])) {
            $params['output_format'] = sanitize_text_field($_POST['output_format']);
        }
        
        if (isset($_POST['output_quality'])) {
            $params['output_quality'] = intval($_POST['output_quality']);
        }
        
        if (isset($_POST['disable_safety_checker'])) {
            $params['disable_safety_checker'] = $_POST['disable_safety_checker'] === 'true';
        }
        
        if (isset($_POST['go_fast'])) {
            $params['go_fast'] = $_POST['go_fast'] === 'true';
        }
        
        // Create prediction
        $result = $this->api->create_prediction($params);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ));
        }
        
        // Save to history
        $this->save_to_history($params, $result);
        
        // Save last used image if enabled
        if (get_option('wp_kontext_gen_remember_last_image') && !empty($params['input_image'])) {
            update_option('wp_kontext_gen_last_image', $params['input_image']);
        }
        
        // Always save last prompt
        update_option('wp_kontext_gen_last_prompt', $params['prompt']);
        
        wp_send_json_success($result);
    }
    
    /**
     * Handle check status request via AJAX
     */
    public function handle_check_status() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        if (empty($_POST['prediction_id'])) {
            wp_send_json_error(array('message' => __('Prediction ID is required', 'wp-kontext-gen')));
        }
        
        $prediction_id = sanitize_text_field($_POST['prediction_id']);
        $result = $this->api->check_prediction_status($prediction_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ));
        }
        
        // Update history if completed
        if ($result['status'] === 'succeeded' || $result['status'] === 'failed') {
            $attachment_id = $this->update_history_status($prediction_id, $result);
            if ($attachment_id) {
                $result['attachment_id'] = $attachment_id;
            }
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Save generation to history
     */
    private function save_to_history($params, $prediction) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        // Custom logging function that works regardless of WP_DEBUG
        $this->log_debug("Starting save_to_history function");
        
        // Ensure database schema is up to date
        $this->migrate_database();
        
        $insert_data = array(
            'user_id' => get_current_user_id(),
            'prompt' => $params['prompt'],
            'input_image_url' => isset($params['input_image']) ? $params['input_image'] : null,
            'parameters' => json_encode($params),
            'status' => $prediction['status'],
            'prediction_id' => $prediction['id'],
        );
        
        $this->log_debug("Attempting to insert data: " . print_r($insert_data, true));
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            $this->log_debug("Failed to insert into history table. Error: " . $wpdb->last_error);
            $this->log_debug("Last query: " . $wpdb->last_query);
        } else {
            $this->log_debug("Successfully inserted into history table. ID: " . $wpdb->insert_id . ", Rows affected: " . $result);
        }
    }
    
    /**
     * Custom debug logging that works regardless of WP_DEBUG settings
     */
    private function log_debug($message) {
        // Always log to a custom file in uploads directory
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/wp-kontext-gen-debug.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message" . PHP_EOL;
        
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
        
        // Also try WordPress error log if available
        if (function_exists('error_log')) {
            error_log("WP Kontext Gen: $message");
        }
    }
    
    /**
     * Create history table if it doesn't exist
     */
    private function create_history_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            prompt text NOT NULL,
            input_image_url text,
            output_image_url text,
            attachment_id bigint(20),
            parameters text,
            status varchar(20) DEFAULT 'pending',
            cost_usd decimal(10,6) DEFAULT NULL,
            prediction_id varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        $this->log_debug("Created history table with result: " . print_r($result, true));
        
        // Update database version
        update_option('wp_kontext_gen_db_version', '1.2.5');
    }
    
    /**
     * Migrate database structure if needed
     */
    private function migrate_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        $current_db_version = get_option('wp_kontext_gen_db_version', '1.0.0');
        $plugin_version = WP_KONTEXT_GEN_VERSION;
        
        $this->log_debug("Checking database migration. Current DB version: $current_db_version, Plugin version: $plugin_version");
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            $this->log_debug("Table doesn't exist, creating new table");
            $this->create_history_table();
            return;
        }
        
        // Check if prediction_id column exists
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        $has_prediction_id = false;
        foreach ($columns as $column) {
            if ($column->Field === 'prediction_id') {
                $has_prediction_id = true;
                break;
            }
        }
        
        if (!$has_prediction_id) {
            $this->log_debug("Missing prediction_id column, adding it");
            $sql = "ALTER TABLE $table_name ADD COLUMN prediction_id varchar(255) AFTER status";
            $result = $wpdb->query($sql);
            if ($result === false) {
                $this->log_debug("Failed to add prediction_id column: " . $wpdb->last_error);
            } else {
                $this->log_debug("Successfully added prediction_id column");
                update_option('wp_kontext_gen_db_version', '1.2.6');
            }
        }
        
        // Check if created_at column exists
        $has_created_at = false;
        foreach ($columns as $column) {
            if ($column->Field === 'created_at') {
                $has_created_at = true;
                break;
            }
        }
        
        if (!$has_created_at) {
            $this->log_debug("Missing created_at column, adding it");
            $sql = "ALTER TABLE $table_name ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER prediction_id";
            $result = $wpdb->query($sql);
            if ($result === false) {
                $this->log_debug("Failed to add created_at column: " . $wpdb->last_error);
            } else {
                $this->log_debug("Successfully added created_at column");
                update_option('wp_kontext_gen_db_version', '1.2.6');
            }
        }
        
        $this->log_debug("Database migration completed");
    }
    
    /**
     * Update history status
     */
    private function update_history_status($prediction_id, $result) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        $update_data = array(
            'status' => $result['status'],
        );
        
        $attachment_id = null;
        
        // Add cost information - FLUX Kontext models charge per image
        if ($result['status'] === 'succeeded') {
            $current_model = get_option('wp_kontext_gen_model', 'dev');
            switch ($current_model) {
                case 'pro':
                    $cost_per_image = 0.04; // $0.04 per image
                    break;
                case 'max':
                    $cost_per_image = 0.08; // $0.08 per image
                    break;
                default: // dev
                    $cost_per_image = 0.025; // $0.025 per image
                    break;
            }
            $update_data['cost_usd'] = $cost_per_image;
        }
        
        if ($result['status'] === 'succeeded' && !empty($result['output'])) {
            $output_url = is_array($result['output']) ? $result['output'][0] : $result['output'];
            $update_data['output_image_url'] = $output_url;
            
            // Save to media library by default
            $media_result = $this->api->save_to_media_library($output_url, 'Kontext Gen - ' . date('Y-m-d H:i:s'));
            if (!is_wp_error($media_result)) {
                $attachment_id = $media_result['id'];
                $update_data['attachment_id'] = $attachment_id;
            }
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('prediction_id' => $prediction_id)
        );
        
        return $attachment_id;
    }
    
    /**
     * Handle delete image request via AJAX
     */
    public function handle_delete_image() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        if (empty($_POST['history_id'])) {
            wp_send_json_error(array('message' => __('History ID is required', 'wp-kontext-gen')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        $history_id = intval($_POST['history_id']);
        
        // Get the history item
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $history_id
        ));
        
        if (!$item) {
            wp_send_json_error(array('message' => __('History item not found', 'wp-kontext-gen')));
        }
        
        // Delete from media library if exists
        if (!empty($item->attachment_id)) {
            wp_delete_attachment($item->attachment_id, true);
        }
        
        // Delete from history
        $wpdb->delete($table_name, array('id' => $history_id));
        
        wp_send_json_success(array('message' => __('Image deleted successfully', 'wp-kontext-gen')));
    }
    
    /**
     * Handle clear history request via AJAX
     */
    public function handle_clear_history() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        // Get all items with attachments
        $items = $wpdb->get_results("SELECT attachment_id FROM $table_name WHERE attachment_id IS NOT NULL");
        
        // Delete all attachments
        foreach ($items as $item) {
            if (!empty($item->attachment_id)) {
                wp_delete_attachment($item->attachment_id, true);
            }
        }
        
        // Clear the table
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        wp_send_json_success(array('message' => __('History cleared successfully', 'wp-kontext-gen')));
    }
    
    /**
     * Handle save to media library request via AJAX
     */
    public function handle_save_to_media_library() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        if (empty($_POST['image_url'])) {
            wp_send_json_error(array('message' => __('Image URL is required', 'wp-kontext-gen')));
        }
        
        $image_url = esc_url_raw($_POST['image_url']);
        $title = sanitize_text_field($_POST['title'] ?? 'Kontext Generated Image');
        
        $result = $this->api->save_to_media_library($image_url, $title);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Image saved to media library successfully', 'wp-kontext-gen'),
            'attachment_id' => $result['id'],
            'url' => $result['url']
        ));
    }
    
    /**
     * Get total cost for current user
     */
    public function get_total_cost() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cost_usd) FROM $table_name WHERE user_id = %d AND cost_usd IS NOT NULL",
            get_current_user_id()
        ));
        
        return floatval($total);
    }
    
    /**
     * Handle check updates request via AJAX
     */
    public function handle_check_updates() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        // Clear cached version data to force fresh check
        delete_transient('wp_kontext_gen_remote_version');
        delete_transient('wp_kontext_gen_remote_info');
        
        // Get current and remote versions
        $current_version = WP_KONTEXT_GEN_VERSION;
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            wp_send_json_error(array('message' => __('Unable to check for updates at this time.', 'wp-kontext-gen')));
        }
        
        $update_available = version_compare($current_version, $remote_version, '<');
        
        wp_send_json_success(array(
            'update_available' => $update_available,
            'current_version' => $current_version,
            'latest_version' => $remote_version,
            'message' => $update_available ? 
                sprintf(__('Update available: v%s', 'wp-kontext-gen'), $remote_version) : 
                __('You have the latest version!', 'wp-kontext-gen')
        ));
    }
    
    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        $response = wp_remote_get('https://api.github.com/repos/nerveband/wp-kontext-gen/releases/latest', array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['tag_name']) ? ltrim($body['tag_name'], 'v') : false;
    }
    
    /**
     * Handle force update check request via AJAX
     */
    public function handle_force_update_check() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        // Clear all update transients
        delete_transient('wp_kontext_gen_remote_version');
        delete_transient('wp_kontext_gen_remote_info');
        delete_site_transient('update_plugins');
        
        // Get current and remote versions
        $current_version = WP_KONTEXT_GEN_VERSION;
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            wp_send_json_error(array('message' => __('Unable to check for updates at this time.', 'wp-kontext-gen')));
        }
        
        $update_available = version_compare($current_version, $remote_version, '<');
        
        // Force WordPress to check for plugin updates
        wp_update_plugins();
        
        wp_send_json_success(array(
            'update_available' => $update_available,
            'current_version' => $current_version,
            'latest_version' => $remote_version,
            'plugin_slug' => plugin_basename(WP_KONTEXT_GEN_PLUGIN_PATH . 'wp-kontext-gen.php'),
            'update_url' => admin_url('plugins.php'),
            'message' => $update_available ? 
                sprintf(__('Update available: v%s. Check your plugins page!', 'wp-kontext-gen'), $remote_version) : 
                __('You have the latest version!', 'wp-kontext-gen')
        ));
    }
    
    /**
     * Handle refresh recent generations request via AJAX
     */
    public function handle_refresh_recent_generations() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        $recent_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 3",
            get_current_user_id()
        ));
        
        ob_start();
        
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
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'message' => __('Recent generations refreshed', 'wp-kontext-gen')
        ));
    }
    
    /**
     * Handle debug database request via AJAX
     */
    public function handle_debug_database() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        $debug_info = array();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        $debug_info['table_exists'] = $table_exists ? 'Yes' : 'No';
        $debug_info['table_name'] = $table_name;
        
        if ($table_exists) {
            // Get table structure
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            $debug_info['table_structure'] = $columns;
            
            // Count records
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $debug_info['record_count'] = $count;
            
            // Get recent records
            $recent = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 5");
            $debug_info['recent_records'] = $recent;
        } else {
            // Try to create table
            $this->create_history_table();
            $debug_info['table_creation_attempted'] = true;
        }
        
        // Check WordPress error log for our messages
        $debug_info['wp_debug'] = defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled';
        $debug_info['wp_debug_log'] = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Enabled' : 'Disabled';
        
        // Get recent log entries
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/wp-kontext-gen-debug.log';
        if (file_exists($log_file)) {
            $log_contents = file_get_contents($log_file);
            $log_lines = explode("\n", $log_contents);
            $debug_info['recent_logs'] = array_slice(array_filter($log_lines), -10); // Last 10 non-empty lines
        } else {
            $debug_info['recent_logs'] = array('No debug log file found');
        }
        
        wp_send_json_success(array(
            'debug_info' => $debug_info,
            'message' => __('Database debug information retrieved', 'wp-kontext-gen')
        ));
    }
    
    /**
     * Handle test database insert via AJAX
     */
    public function handle_test_database_insert() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        $this->log_debug("Starting test database insert");
        
        // Test data
        $test_params = array(
            'prompt' => 'Test prompt for debugging',
            'input_image' => 'https://example.com/test.jpg'
        );
        
        $test_prediction = array(
            'id' => 'test-prediction-' . time(),
            'status' => 'starting'
        );
        
        // Try to insert test data
        $this->save_to_history($test_params, $test_prediction);
        
        // Check if it worked
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE prompt = 'Test prompt for debugging'");
        
        $this->log_debug("Test insert result: $count test records found");
        
        wp_send_json_success(array(
            'test_records_created' => intval($count),
            'message' => sprintf(__('Test insert completed. %d test records found.', 'wp-kontext-gen'), $count)
        ));
    }
    
    /**
     * Handle manual database migration via AJAX
     */
    public function handle_migrate_database() {
        check_ajax_referer('wp_kontext_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-kontext-gen'));
        }
        
        $this->log_debug("Starting manual database migration");
        
        // Run migration
        $this->migrate_database();
        
        // Check table structure after migration
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        $this->log_debug("Migration completed. Current columns: " . implode(', ', $column_names));
        
        wp_send_json_success(array(
            'columns' => $column_names,
            'message' => sprintf(__('Database migration completed. Table now has %d columns.', 'wp-kontext-gen'), count($column_names))
        ));
    }
}