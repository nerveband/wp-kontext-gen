<?php
/**
 * Plugin Name: WP Kontext Gen
 * Plugin URI: https://github.com/nerveband/wp-kontext-gen
 * Description: Generate and edit images using Replicate's FLUX.1 Kontext [dev] model
 * Version: 1.2.4
 * Author: Nerveband
 * License: GPL v2 or later
 * Text Domain: wp-kontext-gen
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_KONTEXT_GEN_VERSION', '1.2.4');
define('WP_KONTEXT_GEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_KONTEXT_GEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WP_KONTEXT_GEN_REPLICATE_API_URL', 'https://api.replicate.com/v1/predictions');
define('WP_KONTEXT_GEN_MODEL_VERSION', 'black-forest-labs/flux-kontext-dev');

// Include required files
require_once WP_KONTEXT_GEN_PLUGIN_PATH . 'includes/class-wp-kontext-gen.php';
require_once WP_KONTEXT_GEN_PLUGIN_PATH . 'includes/class-wp-kontext-gen-api.php';
require_once WP_KONTEXT_GEN_PLUGIN_PATH . 'includes/class-wp-kontext-gen-admin.php';
require_once WP_KONTEXT_GEN_PLUGIN_PATH . 'includes/class-wp-kontext-gen-updater.php';

// Initialize the plugin
function wp_kontext_gen_init() {
    $plugin = new WP_Kontext_Gen();
    $plugin->run();
    
    // Initialize auto-updater
    if (is_admin()) {
        new WP_Kontext_Gen_Updater(__FILE__);
    }
}
add_action('plugins_loaded', 'wp_kontext_gen_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_kontext_gen_activate');
function wp_kontext_gen_activate() {
    // Create database table for storing generation history
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
    dbDelta($sql);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_kontext_gen_deactivate');
function wp_kontext_gen_deactivate() {
    // Clean up scheduled tasks if any
}