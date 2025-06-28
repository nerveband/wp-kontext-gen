<?php
/**
 * Main plugin class
 */

class WP_Kontext_Gen {
    
    protected $loader;
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->plugin_name = 'wp-kontext-gen';
        $this->version = WP_KONTEXT_GEN_VERSION;
        
        $this->load_dependencies();
        $this->define_admin_hooks();
    }
    
    private function load_dependencies() {
        $this->loader = new WP_Kontext_Gen_Loader();
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new WP_Kontext_Gen_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Settings link
        $this->loader->add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__, 2) . '/wp-kontext-gen.php'), $plugin_admin, 'add_settings_link');
        
        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_wp_kontext_gen_generate', $plugin_admin, 'handle_generate_request');
        $this->loader->add_action('wp_ajax_wp_kontext_gen_check_status', $plugin_admin, 'handle_check_status');
        $this->loader->add_action('wp_ajax_wp_kontext_gen_delete_image', $plugin_admin, 'handle_delete_image');
        $this->loader->add_action('wp_ajax_wp_kontext_gen_clear_history', $plugin_admin, 'handle_clear_history');
        $this->loader->add_action('wp_ajax_wp_kontext_gen_save_to_media_library', $plugin_admin, 'handle_save_to_media_library');
        $this->loader->add_action('wp_ajax_wp_kontext_gen_check_updates', $plugin_admin, 'handle_check_updates');
        
        // Settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
    }
    
    public function run() {
        $this->loader->run();
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_version() {
        return $this->version;
    }
}

/**
 * Simple loader class to handle hooks
 */
class WP_Kontext_Gen_Loader {
    
    protected $actions;
    protected $filters;
    
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }
    
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
        
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}