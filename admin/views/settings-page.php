<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

use WPCode\WP_Code_Admin_Settings;

?>

<div class="wrap">
    <h1><?php echo esc_html('Code SDK Settings'); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields(WP_Code_Admin_Settings::OPTION_GROUP);
        do_settings_sections(WP_Code_Admin_Settings::SETTINGS_PAGE);
        submit_button('Save Settings');
        ?>
    </form>
    <form method="post">
        <?php submit_button('Reset to Defaults', 'secondary', 'reset_settings'); ?>
    </form>
</div>

<div class="wrap code-sdk-usage-instructions">
    <h2><?php echo esc_html('Usage Instructions'); ?></h2>
    <p><?php echo esc_html('To add a payment button to your posts or pages, use the following shortcode:'); ?></p>
    <code>[code_payment_button]</code>
    
    <p><?php echo esc_html('You can also customize the currency and amount for individual buttons:'); ?></p>
    <code>[code_payment_button currency="usd" amount="10"]</code>
    
    <p><?php echo esc_html('If no attributes are provided, the button will use the default values set in the configuration above.'); ?></p>
</div>
