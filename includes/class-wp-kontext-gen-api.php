<?php
/**
 * Replicate API integration class
 */

class WP_Kontext_Gen_API {
    
    private $api_key;
    private $model_version;
    
    public function __construct() {
        $this->api_key = get_option('wp_kontext_gen_api_key');
        // Get the latest model version from the API
        $this->model_version = $this->get_model_version();
    }
    
    /**
     * Get the latest model version
     */
    private function get_model_version() {
        $cached_version = get_transient('wp_kontext_gen_model_version');
        if ($cached_version) {
            return $cached_version;
        }
        
        $response = wp_remote_get('https://api.replicate.com/v1/models/black-forest-labs/flux-kontext-dev', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            )
        ));
        
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['latest_version']['id'])) {
                $version = $body['latest_version']['id'];
                set_transient('wp_kontext_gen_model_version', $version, DAY_IN_SECONDS);
                return $version;
            }
        }
        
        // Fallback to a known version if API call fails
        return 'c92f87b95c8b4a88c3f91f9e99c7e8c26b0d0bb0f42c79bfe3f9e5e1e3e3e3e3';
    }
    
    /**
     * Create a prediction
     */
    public function create_prediction($params) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'wp-kontext-gen'));
        }
        
        // Prepare the input data
        $input = array(
            'prompt' => sanitize_text_field($params['prompt']),
        );
        
        // Add optional parameters
        if (!empty($params['input_image'])) {
            $input['input_image'] = esc_url_raw($params['input_image']);
        }
        
        if (!empty($params['aspect_ratio'])) {
            $input['aspect_ratio'] = sanitize_text_field($params['aspect_ratio']);
        }
        
        if (isset($params['num_inference_steps'])) {
            $input['num_inference_steps'] = intval($params['num_inference_steps']);
        }
        
        if (isset($params['guidance'])) {
            $input['guidance'] = floatval($params['guidance']);
        }
        
        if (isset($params['seed'])) {
            $input['seed'] = intval($params['seed']);
        }
        
        if (!empty($params['output_format'])) {
            $input['output_format'] = sanitize_text_field($params['output_format']);
        }
        
        if (isset($params['output_quality'])) {
            $input['output_quality'] = intval($params['output_quality']);
        }
        
        if (isset($params['disable_safety_checker'])) {
            $input['disable_safety_checker'] = (bool)$params['disable_safety_checker'];
        }
        
        if (isset($params['go_fast'])) {
            $input['go_fast'] = (bool)$params['go_fast'];
        }
        
        $body = array(
            'version' => $this->model_version,
            'input' => $input
        );
        
        $response = wp_remote_post(WP_KONTEXT_GEN_REPLICATE_API_URL, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 201) {
            return new WP_Error('api_error', 
                isset($response_body['detail']) ? $response_body['detail'] : __('API request failed', 'wp-kontext-gen'),
                array('status' => $response_code)
            );
        }
        
        return $response_body;
    }
    
    /**
     * Check prediction status
     */
    public function check_prediction_status($prediction_id) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'wp-kontext-gen'));
        }
        
        $response = wp_remote_get(WP_KONTEXT_GEN_REPLICATE_API_URL . '/' . $prediction_id, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 
                isset($response_body['detail']) ? $response_body['detail'] : __('Failed to check status', 'wp-kontext-gen'),
                array('status' => $response_code)
            );
        }
        
        return $response_body;
    }
    
    /**
     * Cancel a prediction
     */
    public function cancel_prediction($prediction_id) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'wp-kontext-gen'));
        }
        
        $response = wp_remote_request(WP_KONTEXT_GEN_REPLICATE_API_URL . '/' . $prediction_id . '/cancel', array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', __('Failed to cancel prediction', 'wp-kontext-gen'));
        }
        
        return true;
    }
    
    /**
     * Validate API key
     */
    public function validate_api_key($api_key = null) {
        if ($api_key === null) {
            $api_key = $this->api_key;
        }
        
        if (empty($api_key)) {
            return false;
        }
        
        $response = wp_remote_get('https://api.replicate.com/v1/models/black-forest-labs/flux-kontext-dev', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
    
    /**
     * Download image and save to media library
     */
    public function save_to_media_library($image_url, $title = '') {
        if (empty($image_url)) {
            return new WP_Error('no_image_url', __('No image URL provided', 'wp-kontext-gen'));
        }
        
        // Download the image
        $response = wp_remote_get($image_url, array(
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        // Determine file extension from content type
        $extension = 'jpg';
        if (strpos($content_type, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($content_type, 'webp') !== false) {
            $extension = 'webp';
        }
        
        // Generate filename
        $filename = 'kontext-gen-' . date('Y-m-d-His') . '.' . $extension;
        
        // Upload to media library
        $upload = wp_upload_bits($filename, null, $image_data);
        
        if ($upload['error']) {
            return new WP_Error('upload_error', $upload['error']);
        }
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $content_type,
            'post_title' => !empty($title) ? $title : 'Kontext Generated Image',
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return array(
            'id' => $attach_id,
            'url' => wp_get_attachment_url($attach_id),
            'file' => $upload['file']
        );
    }
}