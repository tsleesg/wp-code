<?php

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete the plugin options
$option_name = 'code_sdk_wp_options';
$options = get_option($option_name);

if ($options) {
    $fields_to_delete = array(
        'store_account',
        'webhook_url',
        'sequencer_public_key',
        'min_amount',
        'default_amount',
        'currency',
        'destination_address',
        'debug_mode'
    );

    foreach ($fields_to_delete as $field) {
        unset($options[$field]);
    }

    update_option($option_name, $options);
}

delete_option($option_name);

// Clear any cached data that may have been stored
wp_cache_flush();
