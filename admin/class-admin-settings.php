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
        add_action('admin_init', array($this, 'create_code_payments_json'));
    }

    public function create_code_payments_json() {
        $options = get_option(self::OPTION_NAME, array());
        $public_key = $options['login_verifier_public_key'] ?? '';
    
        if (!empty($public_key)) {
            $json_data = json_encode(array(
                "public_keys" => [$public_key]
            ));
    
            $file_path = ABSPATH . '.well-known/code-payments.json';
            $dir_path = dirname($file_path);
    
            if (!file_exists($dir_path)) {
                wp_mkdir_p($dir_path);
            }
    
            file_put_contents($file_path, $json_data);
        }
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
            'debug_mode' => 'Debug Mode',
            'login_verifier_public_key' => 'Login Verifier Public Key',
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
        $this->render_text_field('destination_address', 'E8otxw1CVX9bfyddKu3ZB3BVLa4VVF9J7CTPdnUwT9jR');
    }

    public function debug_mode_callback() {
        printf(
            '<input type="checkbox" id="debug_mode" name="%s[debug_mode]" value="1" %s />',
            self::OPTION_NAME,
            isset($this->options['debug_mode']) && $this->options['debug_mode'] ? 'checked' : ''
        );
    }
    
    public function login_verifier_public_key_callback() {
        $this->render_text_field('login_verifier_public_key', '');
        echo '<button type="button" id="generate_public_key" class="button">Generate New Key</button>';
        
        $raw_public_key = isset($this->options['raw_login_verifier_public_key']) ? $this->options['raw_login_verifier_public_key'] : '';
        echo '<div style="margin-top: 10px;"><strong>Raw Public Key:</strong> <span id="raw_public_key">' . esc_html($raw_public_key) . '</span></div>';
        
        ?>
        <script type="module">
            import { Keypair } from 'https://esm.sh/@code-wallet/keys';
            
            document.getElementById('generate_public_key').addEventListener('click', () => {
                try {
                    const verifier = Keypair.generate();
                    const rawPublicKey = verifier.publicKey.toString();
                    const publicKey = verifier.getPublicKey().toBase58();
                    
                    document.getElementById('login_verifier_public_key').value = publicKey;
                    document.getElementById('raw_public_key').textContent = rawPublicKey;
                    
                    let hiddenInput = document.getElementById('raw_login_verifier_public_key');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.id = 'raw_login_verifier_public_key';
                        hiddenInput.name = '<?php echo self::OPTION_NAME; ?>[raw_login_verifier_public_key]';
                        document.getElementById('login_verifier_public_key').parentNode.appendChild(hiddenInput);
                    }
                    hiddenInput.value = rawPublicKey;
                } catch (error) {
                    console.error('Error generating keypair:', error);
                    alert('Failed to generate public key: ' + error.message);
                }
            });
        </script>
        <?php
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
        $fields = array('webhook_url', 'sequencer_public_key', 'min_amount', 'default_amount', 'currency', 'destination_address', 'login_verifier_public_key', 'raw_login_verifier_public_key');
    
        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $new_input[$field] = sanitize_text_field($input[$field]);
            }
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
                'destination_address' => 'E8otxw1CVX9bfyddKu3ZB3BVLa4VVF9J7CTPdnUwT9jR',
                'debug_mode' => false,
            );
            update_option(self::OPTION_NAME, $default_options);
            add_settings_error(self::OPTION_NAME, 'settings_reset', 'Settings have been reset to defaults', 'updated');
        }
    }

    public function get_config() {
        $options = get_option(self::OPTION_NAME, array());
        $config = array(
            'destination_address' => $options['destination_address'] ?? 'E8otxw1CVX9bfyddKu3ZB3BVLa4VVF9J7CTPdnUwT9jR',
            'webhook_url' => $options['webhook_url'] ?? site_url('/wp-json/wp-code/v1/webhook'),
            'sequencer_public_key' => $options['sequencer_public_key'] ?? 'codeHy87wGD5oMRLG75qKqsSi1vWE3oxNyYmXo5F9YR',
            'min_amount' => $options['min_amount'] ?? 2500,
            'default_amount' => $options['default_amount'] ?? 5000,
            'currency' => $options['currency'] ?? 'kin',
            'login_verifier_public_key' => $options['login_verifier_public_key'] ?? '',
            'version' => defined('CODE_SDK_WP_VERSION') ? CODE_SDK_WP_VERSION : '1.0.0',
            'plugin_url' => defined('CODE_SDK_WP_PLUGIN_URL') ? CODE_SDK_WP_PLUGIN_URL : plugin_dir_url(__FILE__),
        );
        return $config;
    }
}
