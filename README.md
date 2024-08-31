# Code Payments Integration for WordPress

This WordPress plugin integrates Code SDK features into WordPress, providing seamless payment requests, user authentication, post unlocking, dark mode support, and transaction verification.

## Features

- Payment request functionality
- User authentication with Code Wallet
- Post unlocking mechanism
- Dark mode support
- Transaction verification
- Admin settings page for easy configuration

## Installation

1. Upload the `wp-code` folder to the `/wp-plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings in the WordPress admin area

## Usage

### Shortcodes

- `[code_payment_request amount="10" currency="kin"]` - Displays a payment request form
- `[code_user_auth]` - Displays a user authentication form
- `[code_wallet_balance]` - Displays the user's wallet balance
- `[code_transaction_history]` - Displays the user's transaction history
- `[code_success_page]` - Renders the transaction success page

### Post Unlocking

The plugin automatically adds an unlock button to posts with restricted content. Users can pay to unlock the full content.

### Admin Settings

Navigate to the WordPress admin area and go to Settings > Code SDK to configure the plugin:

- API Key
- Destination Address
- Debug Mode

## Development

### Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- Composer for dependency management

### Setup

1. Clone the repository
2. Run `composer install` to install dependencies
3. Ensure your development environment meets the requirements

## License

This project is licensed under the AGPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please open an issue on the GitHub repository or contact the plugin author.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.