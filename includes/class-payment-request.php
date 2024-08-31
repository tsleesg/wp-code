<?php

namespace WPCode;

class WP_Code_Payment_Request {
    private $config;

    public function __construct() {
        $admin_settings = new WP_Code_Admin_Settings();
        $this->config = $admin_settings->get_config();
        add_shortcode('code_payment_button', array($this, 'render_button_shortcode'));
    }

    public function render_button_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'currency' => $this->config['currency'],
                'amount' => $this->config['default_amount'],
            ),
            $atts
        );
    
        ob_start();
        ?>
        <div id="payment-container">
            <div id="button-container"></div>
            <div id="thank-you-message" style="display: none; margin-top: 10px; font-weight: bold;"></div>
        </div>
    
        <script type="module">
            import CodeWallet from 'https://js.getcode.com/v1/';
    
            if (CodeWallet && CodeWallet.elements && !window.codeWalletButtonMounted) {
                const { button } = CodeWallet.elements.create('button', {
                    currency: '<?php echo esc_js($attributes['currency']); ?>',
                    destination: '<?php echo esc_js($this->config['destination_address']); ?>',
                    amount: <?php echo intval($attributes['amount']); ?>
                });
    
                button.on('success', () => {
                    const buttonContainer = document.getElementById('button-container');
                    buttonContainer.style.display = 'none';
    
                    const thankYouElement = document.getElementById('thank-you-message');
                    thankYouElement.textContent = 'Thank you for your payment!';
                    thankYouElement.style.display = 'block';
                });
    
                button.mount('#button-container');
                window.codeWalletButtonMounted = true;
            }
        </script>
        <?php
        return ob_get_clean();
    }
     
    
}

new WP_Code_Payment_Request();
