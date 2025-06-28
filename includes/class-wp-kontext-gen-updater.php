<?php
/**
 * Plugin updater class for GitHub releases
 */

class WP_Kontext_Gen_Updater {
    
    private $plugin_slug;
    private $version;
    private $plugin_path;
    private $plugin_file;
    private $github_repo;
    private $github_user;
    
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->plugin_path = dirname($plugin_file);
        $this->version = WP_KONTEXT_GEN_VERSION;
        $this->github_user = 'nerveband';
        $this->github_repo = 'wp-kontext-gen';
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_pre_download', array($this, 'download_package'), 10, 3);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => $this->get_github_repo_url(),
                'package' => $this->get_download_url($remote_version),
            );
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for update screen
     */
    public function plugin_info($res, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== dirname($this->plugin_slug)) {
            return $res;
        }
        
        $remote_version = $this->get_remote_version();
        $info = $this->get_remote_info();
        
        $res = (object) array(
            'name' => $info['name'] ?? 'WP Kontext Gen',
            'slug' => dirname($this->plugin_slug),
            'version' => $remote_version,
            'author' => '<a href="https://github.com/nerveband">Nerveband</a>',
            'author_profile' => 'https://github.com/nerveband',
            'last_updated' => $info['date'] ?? date('Y-m-d'),
            'homepage' => $this->get_github_repo_url(),
            'short_description' => 'Generate and edit images using Replicate\'s FLUX.1 Kontext [dev] model',
            'sections' => array(
                'description' => $this->get_description(),
                'installation' => $this->get_installation_instructions(),
                'changelog' => $this->get_changelog(),
            ),
            'download_link' => $this->get_download_url($remote_version),
            'banners' => array(),
            'requires' => '5.0',
            'tested' => '6.4',
            'requires_php' => '7.2',
        );
        
        return $res;
    }
    
    /**
     * Handle plugin download
     */
    public function download_package($reply, $package, $upgrader) {
        if (strpos($package, 'github.com/nerveband/wp-kontext-gen') !== false) {
            return $upgrader->fs_connect(array(WP_CONTENT_DIR, WP_PLUGIN_DIR));
        }
        return $reply;
    }
    
    /**
     * Get remote version from GitHub releases
     */
    private function get_remote_version() {
        $cached = get_transient('wp_kontext_gen_remote_version');
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($this->get_api_url('releases/latest'), array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return $this->version;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $version = isset($body['tag_name']) ? ltrim($body['tag_name'], 'v') : $this->version;
        
        set_transient('wp_kontext_gen_remote_version', $version, HOUR_IN_SECONDS);
        
        return $version;
    }
    
    /**
     * Get remote plugin information
     */
    private function get_remote_info() {
        $cached = get_transient('wp_kontext_gen_remote_info');
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($this->get_github_repo_url() . '/releases.json', array(
            'timeout' => 10,
        ));
        
        $info = array();
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $info = json_decode(wp_remote_retrieve_body($response), true) ?: array();
        }
        
        set_transient('wp_kontext_gen_remote_info', $info, HOUR_IN_SECONDS);
        
        return $info;
    }
    
    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return sprintf(
            'https://github.com/%s/%s/releases/download/v%s/wp-kontext-gen-v%s.zip',
            $this->github_user,
            $this->github_repo,
            $version,
            $version
        );
    }
    
    /**
     * Get GitHub API URL
     */
    private function get_api_url($endpoint = '') {
        return sprintf(
            'https://api.github.com/repos/%s/%s/%s',
            $this->github_user,
            $this->github_repo,
            $endpoint
        );
    }
    
    /**
     * Get GitHub repository URL
     */
    private function get_github_repo_url() {
        return sprintf('https://github.com/%s/%s', $this->github_user, $this->github_repo);
    }
    
    /**
     * Get plugin description
     */
    private function get_description() {
        return '<h3>WP Kontext Gen</h3>
        <p>A powerful WordPress plugin that integrates Replicate\'s FLUX.1 Kontext [dev] model for AI-powered image generation and editing.</p>
        
        <h4>Features:</h4>
        <ul>
            <li><strong>Text-based Image Editing:</strong> Edit images using natural language prompts</li>
            <li><strong>Style Transfer:</strong> Convert photos to different art styles</li>
            <li><strong>Object Modification:</strong> Change colors, add/remove elements</li>
            <li><strong>Background Replacement:</strong> Swap backgrounds while preserving subjects</li>
            <li><strong>WordPress Integration:</strong> Saves images directly to media library</li>
            <li><strong>Generation History:</strong> Track and manage all your generations</li>
            <li><strong>Customizable Parameters:</strong> Full control over all model settings</li>
        </ul>';
    }
    
    /**
     * Get installation instructions
     */
    private function get_installation_instructions() {
        return '<ol>
            <li>Download the plugin ZIP file</li>
            <li>Go to WordPress Admin > Plugins > Add New</li>
            <li>Click "Upload Plugin" and select the ZIP file</li>
            <li>Activate the plugin</li>
            <li>Get your Replicate API key from <a href="https://replicate.com/account/api-tokens" target="_blank">Replicate</a></li>
            <li>Go to Kontext Gen > Settings and enter your API key</li>
            <li>Start generating images!</li>
        </ol>';
    }
    
    /**
     * Get changelog from GitHub releases
     */
    private function get_changelog() {
        $response = wp_remote_get($this->get_api_url('releases'), array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return '<p>Unable to fetch changelog at this time.</p>';
        }
        
        $releases = json_decode(wp_remote_retrieve_body($response), true);
        $changelog = '<div class="wp-kontext-gen-changelog">';
        
        foreach (array_slice($releases, 0, 5) as $release) {
            $version = ltrim($release['tag_name'], 'v');
            $date = date('F j, Y', strtotime($release['published_at']));
            $body = wp_kses_post($release['body']);
            
            $changelog .= sprintf(
                '<h4>Version %s <small>(%s)</small></h4>%s<hr>',
                $version,
                $date,
                wpautop($body)
            );
        }
        
        $changelog .= '</div>';
        
        return $changelog;
    }
}