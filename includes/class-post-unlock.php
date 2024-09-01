<?php

namespace WPCode;

class PostUnlock extends WP_Code_Payment_Request {
    protected $config;

    public function __construct() {
        parent::__construct();
        $this->init_hooks();
        $this->config = $this->get_config();
    }

    private function get_config() {
        $admin_settings = new WP_Code_Admin_Settings();
        return $admin_settings->get_config();
    }

    private function init_hooks(): void {
        add_filter('the_content', [$this, 'filter_post_content']);
        add_action('add_meta_boxes', [$this, 'add_post_meta_box']);
        add_action('save_post', [$this, 'save_post_meta']);
        add_action('wp_footer', [$this, 'enqueue_code_wallet_script']);
    }

    public function enqueue_code_wallet_script(): void {
        ?>
        <script type="module">
            import CodeWallet from 'https://js.getcode.com/v1/';
            window.CodeWallet = CodeWallet;
        </script>
        <?php
    }

    public function filter_post_content(string $content): string {
        global $post;
        
        $unlock_price = $this->get_unlock_price($post->ID);
        
        if ($unlock_price > 0) {
            $teaser_length = $this->config['teaser_length'] ?? 50;
            $teaser = wp_trim_words($content, $teaser_length, '...');
            $unlock_button = $this->render_button_shortcode([
                'amount' => $unlock_price,
                'post_id' => $post->ID,
                'full_content' => $content,
            ]);
            return $teaser . $unlock_button;
        }
        
        return $content;
    }

    private function get_unlock_price(int $post_id): int {
        return (int) get_post_meta($post_id, 'wp_code_unlock_price', true);
    }

    public function add_post_meta_box(): void {
        add_meta_box(
            'wp_code_unlock_price',
            'Unlock Price (KIN)',
            [$this, 'render_meta_box'],
            'post',
            'side',
            'default'
        );
    }

    public function render_meta_box($post): void {
        $value = $this->get_unlock_price($post->ID);
        echo '<label for="wp_code_unlock_price">Price in KIN:</label>';
        echo '<input type="number" id="wp_code_unlock_price" name="wp_code_unlock_price" value="' . esc_attr($value) . '" min="0" step="1">';
        echo '<p>Set to 0 for free access. Minimum allowed value: ' . $this->config['default_amount'] . ' KIN</p>';
    }

    public function save_post_meta($post_id): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['wp_code_unlock_price'])) {
            $price = intval($_POST['wp_code_unlock_price']);
            if ($price > 0 && $price < $this->config['default_amount']) {
                $price = $this->config['default_amount'];
            }
            update_post_meta($post_id, 'wp_code_unlock_price', $price);
        }
    }

    public function render_button_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'currency' => $this->config['currency'],
                'amount' => $this->config['default_amount'],
                'post_id' => get_the_ID(),
                'full_content' => '',
            ),
            $atts
        );
    
        $website_public_key = get_option('code_sdk_wp_options')['website_public_key'] ?? '';
    
        ob_start();
        ?>
        <div id="payment-container" data-full-content="<?php echo esc_attr(wp_kses_post($attributes['full_content'])); ?>">
            <div id="button-container"></div>
            <div id="thank-you-message" style="display: none; margin-top: 10px; font-weight: bold;"></div>
        </div>
    
        <script type="module">
            import code from 'https://js.getcode.com/v1';
    
            const { button } = code.elements.create('button', {
                currency: '<?php echo esc_js($attributes['currency']); ?>',
                destination: '<?php echo esc_js($this->config['destination_address']); ?>',
                amount: <?php echo intval($attributes['amount']); ?>,
                verifier: '<?php echo esc_js($website_public_key); ?>'
            });
    
            button.on('success', () => {
                const buttonContainer = document.getElementById('button-container');
                buttonContainer.style.display = 'none';
    
                const thankYouElement = document.getElementById('thank-you-message');
                thankYouElement.textContent = 'Thank you for your payment!';
                thankYouElement.style.display = 'block';
    
                const postContent = document.querySelector('.entry-content');
                const paymentContainer = document.getElementById('payment-container');
                if (postContent && paymentContainer) {
                    const fullContent = paymentContainer.dataset.fullContent;
                    postContent.innerHTML = fullContent;
                }
            });
    
            button.mount('#button-container');
        </script>
    
        <?php
        return ob_get_clean();
    }
    
}
