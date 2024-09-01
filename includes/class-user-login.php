<?php

namespace WPCode;

class WP_Code_User_Login {
    private $config;
    private $verifier;

    public function __construct() {
        $admin_settings = new WP_Code_Admin_Settings();
        $this->config = $admin_settings->get_config();
        $this->verifier = $this->config['login_verifier_public_key'] ?? '';
        $this->init_hooks();
    }    

    private function init_hooks(): void {
        add_shortcode('code_login_button', array($this, 'render_login_button_shortcode'));
        add_action('init', array($this, 'handle_login_verification'));
    }
    
    public function render_login_button_shortcode($atts) {
        ob_start();
        ?>
        <div id="login-container">
            <div id="login-button-container"></div>
        </div>
    
        <script type="module">
            import code from 'https://js.getcode.com/v1';
    
            const verifier = '<?php echo esc_js($this->verifier); ?>';
            const domain = '<?php echo esc_js(parse_url(home_url(), PHP_URL_HOST)); ?>';
    
            console.log('Code SDK Login Verifier (in shortcode):', verifier);
    
            const { button } = code.elements.create('button', {
                mode: 'login',
    
                login: {
                    verifier, 
                    domain,
                },
    
                confirmParams: {
                    success: { url: '<?php echo esc_js(add_query_arg('code_login_verify', '1', home_url())); ?>', },
                    cancel: { url: '<?php echo esc_js(home_url()); ?>', },
                },
            });
    
            button.mount('#login-button-container');
        </script>
        <?php
        return ob_get_clean();
    }    

    public function handle_login_verification() {
        if (isset($_GET['code_login_verify']) && isset($_GET['intent'])) {
            $intent = sanitize_text_field($_GET['intent']);
            
            // Verify the login intent with Code SDK
            $code_user_id = $this->verify_code_login($intent);
            
            if ($code_user_id) {
                $wp_user_id = $this->get_or_create_wp_user($code_user_id);
                
                if ($wp_user_id) {
                    wp_set_auth_cookie($wp_user_id);
                    wp_safe_redirect(home_url('/wp-admin/'));
                    exit;
                }
            }
            
            // If verification fails or user creation fails, redirect to login page with an error
            wp_safe_redirect(wp_login_url() . '?login=failed');
            exit;
        }
    }
    
    private function verify_code_login($intent) {
        ob_start();
        ?>
        <script type="module">
            import code from 'https://js.getcode.com/v1';
    
            const verifier = '<?php echo esc_js($this->verifier); ?>';
            const intent = '<?php echo esc_js($intent); ?>';
    
            try {
                const result = await code.verifyLogin(intent, verifier);
                if (result.isValid) {
                    document.getElementById('code-login-result').textContent = result.userId;
                } else {
                    document.getElementById('code-login-result').textContent = 'invalid';
                }
            } catch (error) {
                console.error('Login verification failed:', error);
                document.getElementById('code-login-result').textContent = 'error';
            }
        </script>
        <div id="code-login-result" style="display:none;"></div>
        <?php
        echo ob_get_clean();
    
        // Wait for the JavaScript to complete
        sleep(2);
    
        $result = $_POST['code-login-result'] ?? '';
    
        if ($result && $result !== 'invalid' && $result !== 'error') {
            return $result; // This is the userId
        }
    
        return false;
    }     

    public function add_well_known_code_payments() {
        add_rewrite_rule('^\.well-known/code-payments\.json$', 'index.php?well-known-code-payments=1', 'top');
        add_filter('query_vars', function($vars) {
            $vars[] = 'well-known-code-payments';
            return $vars;
        });
        add_action('template_redirect', function() {
            if (get_query_var('well-known-code-payments')) {
                header('Content-Type: application/json');
                $admin_settings = new WP_Code_Admin_Settings();
                $options = get_option($admin_settings::OPTION_NAME, array());
                $public_key = $options['login_verifier_public_key'] ?? '';
                echo json_encode(array(
                    "public_keys" => [$public_key]
                ));
                exit;
            }
        });
    }    

    private function get_or_create_wp_user($code_user_id) {
        $user = get_users(array('meta_key' => 'code_user_id', 'meta_value' => $code_user_id, 'number' => 1));

        if (!empty($user)) {
            return $user[0]->ID;
        }

        $username = 'code_user_' . $code_user_id;
        $email = $username . '@example.com';

        $user_id = wp_create_user($username, wp_generate_password(), $email);

        if (!is_wp_error($user_id)) {
            update_user_meta($user_id, 'code_user_id', $code_user_id);
            return $user_id;
        }

        return false;
    }
}

new WP_Code_User_Login();
