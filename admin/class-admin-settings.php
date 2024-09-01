<?php

namespace WPCode;

class WP_Code_Admin_Settings {
    const OPTION_GROUP = 'code_sdk_wp_option_group';
    const OPTION_NAME = 'code_sdk_wp_options';
    const SETTINGS_PAGE = 'code-sdk-settings';

    private $options;

    public function init() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_init', array($this, 'reset_settings'));
    }

    public function add_plugin_page() {
        add_options_page(
            'Code SDK Settings',
            'Code SDK',
            'manage_options',
            self::SETTINGS_PAGE,
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option(self::OPTION_NAME);
        include(CODE_SDK_WP_PLUGIN_DIR . 'admin/views/settings-page.php');
    }

    public function page_init() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array($this, 'sanitize')
        );

        add_settings_section(
            'code_sdk_wp_setting_section',
            'General Settings',
            array($this, 'section_info'),
            self::SETTINGS_PAGE
        );

        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        $fields = array(
            'webhook_url' => 'Webhook URL',
            'sequencer_public_key' => 'Sequencer Public Key',
            'min_amount' => 'Minimum Amount',
            'default_amount' => 'Default Amount',
            'currency' => 'Currency',
            'destination_address' => 'Destination Address',
            'teaser_length' => 'Teaser Preview Length',
            'debug_mode' => 'Debug Mode'
        );

        foreach ($fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, $field . '_callback'),
                self::SETTINGS_PAGE,
                'code_sdk_wp_setting_section'
            );
        }
    }

    public function section_info() {
        echo 'Enter your settings below:';
    }

    public function webhook_url_callback() {
        $this->render_text_field('webhook_url', site_url('/wp-json/wp-code/v1/webhook'));
    }

    public function sequencer_public_key_callback() {
        $this->render_text_field('sequencer_public_key', 'codeHy87wGD5oMRLG75qKqsSi1vWE3oxNyYmXo5F9YR');
    }

    public function min_amount_callback() {
        $this->render_number_field('min_amount', 2500);
    }

    public function default_amount_callback() {
        $this->render_number_field('default_amount', 5000);
    }

    public function currency_callback() {
        $this->render_text_field('currency', 'kin');
    }

    public function destination_address_callback() {
        $this->render_text_field('destination_address', 'XXXXXXXX');
    }

    public function teaser_length_callback() {
        $this->render_number_field('teaser_length', 50);
    }

    public function debug_mode_callback() {
        printf(
            '<input type="checkbox" id="debug_mode" name="%s[debug_mode]" value="1" %s />',
            self::OPTION_NAME,
            isset($this->options['debug_mode']) && $this->options['debug_mode'] ? 'checked' : ''
        );
    }

    private function render_text_field($field_name, $default_value) {
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" />',
            $field_name,
            self::OPTION_NAME,
            $field_name,
            isset($this->options[$field_name]) ? esc_attr($this->options[$field_name]) : $default_value
        );
    }

    private function render_number_field($field_name, $default_value) {
        printf(
            '<input type="number" id="%s" name="%s[%s]" value="%s" />',
            $field_name,
            self::OPTION_NAME,
            $field_name,
            isset($this->options[$field_name]) ? esc_attr($this->options[$field_name]) : $default_value
        );
    }

    public function sanitize($input) {
        $new_input = array();
        $fields = array('webhook_url', 'sequencer_public_key', 'min_amount', 'default_amount', 'currency', 'destination_address', 'teaser_length');

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $new_input[$field] = sanitize_text_field($input[$field]);
            }
        }

        if (isset($input['teaser_length'])) {
            $new_input['teaser_length'] = intval($input['teaser_length']);
        }

        $new_input['debug_mode'] = isset($input['debug_mode']) ? 1 : 0;

        return $new_input;
    }

    public function reset_settings() {
        if (isset($_POST['reset_settings'])) {
            $default_options = array(
                'webhook_url' => site_url('/wp-json/wp-code/v1/webhook'),
                'sequencer_public_key' => 'codeHy87wGD5oMRLG75qKqsSi1vWE3oxNyYmXo5F9YR',
                'min_amount' => 2500,
                'default_amount' => 5000,
                'currency' => 'kin',
                'destination_address' => 'XXXXXXXX',
                'teaser_length' => 50,
                'debug_mode' => false,
            );
            update_option(self::OPTION_NAME, $default_options);
            add_settings_error(self::OPTION_NAME, 'settings_reset', 'Settings have been reset to defaults', 'updated');
        }
    }

    public function get_config() {
        $options = get_option(self::OPTION_NAME, array());
        $config = array(
            'destination_address' => $options['destination_address'] ?? 'XXXXXXXX',
            'webhook_url' => $options['webhook_url'] ?? site_url('/wp-json/wp-code/v1/webhook'),
            'sequencer_public_key' => $options['sequencer_public_key'] ?? 'codeHy87wGD5oMRLG75qKqsSi1vWE3oxNyYmXo5F9YR',
            'min_amount' => $options['min_amount'] ?? 2500,
            'default_amount' => $options['default_amount'] ?? 5000,
            'currency' => $options['currency'] ?? 'kin',
            'teaser_length' => $options['teaser_length'] ?? 50,
            'version' => defined('CODE_SDK_WP_VERSION') ? CODE_SDK_WP_VERSION : '1.0.0',
            'plugin_url' => defined('CODE_SDK_WP_PLUGIN_URL') ? CODE_SDK_WP_PLUGIN_URL : plugin_dir_url(__FILE__),
        );
        return $config;
    }
}
