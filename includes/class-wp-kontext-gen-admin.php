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
            $this->update_history_status($prediction_id, $result);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Save generation to history
     */
    private function save_to_history($params, $prediction) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kontext_gen_history';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'prompt' => $params['prompt'],
                'input_image_url' => isset($params['input_image']) ? $params['input_image'] : null,
                'parameters' => json_encode($params),
                'status' => $prediction['status'],
                'prediction_id' => $prediction['id'],
            )
        );
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
        
        // Add cost information if available
        if (isset($result['metrics']['predict_time'])) {
            // Estimate cost based on prediction time
            // FLUX.1 Kontext typically costs around $0.003 per second
            $predict_time = floatval($result['metrics']['predict_time']);
            $estimated_cost = $predict_time * 0.003;
            $update_data['cost_usd'] = $estimated_cost;
        }
        
        if ($result['status'] === 'succeeded' && !empty($result['output'])) {
            $output_url = is_array($result['output']) ? $result['output'][0] : $result['output'];
            $update_data['output_image_url'] = $output_url;
            
            // Save to media library
            $media_result = $this->api->save_to_media_library($output_url, 'Kontext Gen - ' . date('Y-m-d H:i:s'));
            if (!is_wp_error($media_result)) {
                $update_data['attachment_id'] = $media_result['id'];
            }
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('prediction_id' => $prediction_id)
        );
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
}