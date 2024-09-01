<?php
/**
 * Plugin Name: Code Payments Integration for WordPress
 * Plugin URI: https://github.com/ooai/wp-plugins
 * Description: Integrates Code SDK features into WordPress for payment requests and post unlocking.
 * Version: 1.0.1
 * Author: Code Wallet
 * Author URI: https://ourown.ai
 * License: AGPL-3.0
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain: wp-code
 * Domain Path: /languages
 */

namespace WPCode;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define constants
define('CODE_SDK_WP_VERSION', '1.0.1');
define('CODE_SDK_WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CODE_SDK_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include the necessary files
require_once CODE_SDK_WP_PLUGIN_DIR . 'admin/class-admin-settings.php';
require_once CODE_SDK_WP_PLUGIN_DIR . 'includes/class-payment-request.php';
require_once CODE_SDK_WP_PLUGIN_DIR . 'includes/class-post-unlock.php';
require_once CODE_SDK_WP_PLUGIN_DIR . 'includes/class-user-login.php';

// Initialize the plugin
function run_wp_code() {
    $admin_settings = new WP_Code_Admin_Settings();
    $admin_settings->init();

    $config = $admin_settings->get_config();
    new WP_Code_Payment_Request();
    new PostUnlock($config);
    new WP_Code_User_Login();
}

add_action('plugins_loaded', 'WPCode\\run_wp_code');

// Register activation hook
register_activation_hook(__FILE__, 'WPCode\\activate_wp_code');

function activate_wp_code() {
    $admin_settings = new WP_Code_Admin_Settings();
    $admin_settings->create_code_payments_json();
    code_sdk_flush_rewrite_rules();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'WPCode\\deactivate_wp_code');

function deactivate_wp_code() {
    // Deactivation tasks, if any
}

// Add rewrite rule for .well-known/code-payments.json
function code_sdk_add_rewrite_rule() {
    add_rewrite_rule('^\.well-known/code-payments\.json$', 'index.php?well-known=code-payments', 'top');
}
add_action('init', 'WPCode\\code_sdk_add_rewrite_rule');

// Add query var for well-known
function code_sdk_query_vars($vars) {
    $vars[] = 'well-known';
    return $vars;
}
add_filter('query_vars', 'WPCode\\code_sdk_query_vars');

// Flush rewrite rules on activation
function code_sdk_flush_rewrite_rules() {
    $user_login = new WP_Code_User_Login();
    $user_login->add_well_known_code_payments();
    flush_rewrite_rules();
}
