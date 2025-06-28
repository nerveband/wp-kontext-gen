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
            'nonce' => wp_create_nonce('wp_kontext_gen_nonce'),
            'strings' => array(
                'generating' => __('Generating...', 'wp-kontext-gen'),
                'error' => __('An error occurred', 'wp-kontext-gen'),
                'select_image' => __('Select Input Image', 'wp-kontext-gen'),
                'use_image' => __('Use This Image', 'wp-kontext-gen'),
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
        
        if ($result['status'] === 'succeeded' && !empty($result['output'])) {
            $update_data['output_image_url'] = is_array($result['output']) ? $result['output'][0] : $result['output'];
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $prediction_id)
        );
    }
}