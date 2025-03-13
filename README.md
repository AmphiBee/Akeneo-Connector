# Akeneo Connector for WooCommerce

## Overview

The Akeneo Connector for WooCommerce is a powerful integration tool that synchronizes product data between Akeneo PIM (Product Information Management) and WooCommerce. This connector enables seamless product data management by importing various entities from Akeneo to your WooCommerce store.

## Features

- **Comprehensive Data Import**: Import categories, attributes, attribute options, product models, and products from Akeneo to WooCommerce.
- **Smart Change Detection**: Uses hash-based change detection to only import modified data, significantly improving performance.
- **Multilingual Support**: Full support for multilingual content with automatic translation synchronization.
- **Robust Error Handling**: Advanced error logging and retry mechanisms for API calls.
- **WP-CLI Commands**: Convenient command-line interface for running imports and migrations.
- **Variable Products Support**: Complete support for Akeneo product models as WooCommerce variable products.

## Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- Akeneo PIM 4.0+
- PHP 7.4+

## Installation

1. Upload the plugin files to the `/wp-content/plugins/akeneo-connector` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the Akeneo API credentials in the plugin settings.

## Configuration

### API Credentials

Navigate to the Akeneo Connector settings page and enter your Akeneo API credentials:

- Akeneo Host URL
- Client ID
- Client Secret
- Username
- Password
- Channel (optional)

### Retry Configuration

The connector includes a configurable retry mechanism for API calls:

```php
'retry' => [
    'enabled' => true,
    'max_retries' => 3,
    'delay' => 2,
    'status_codes' => [429, 500, 502, 503, 504],
],
```

## Usage

### WP-CLI Commands

The connector provides several WP-CLI commands for importing data:

```bash
# Import categories
wp akeneo categories import

# Import attributes
wp akeneo attributes import

# Import attribute options
wp akeneo attribute_options import

# Import product models (variable products)
wp akeneo models import

# Import products
wp akeneo products import

# Run migrations
wp akeneo migrate_hash_column run
wp akeneo migrate_attribute_hash run
```

### Scheduled Imports

You can set up cron jobs to run imports automatically:

```bash
# Example cron job to import products every hour
0 * * * * cd /path/to/wordpress && wp akeneo products import --path=/path/to/wordpress
```

## Data Mapping

The connector uses a mapping system to determine how Akeneo attributes should be imported into WooCommerce:

- **global_attribute**: Imported as a WooCommerce global attribute (visible to customers)
- **private_global_attribute**: Imported as a WooCommerce global attribute (not visible to customers)
- **text_attribute**: Imported as a WooCommerce text attribute
- **post_meta**: Imported as a product meta field
- **external_media**: Imported as external media

## Performance Optimization

The connector implements several performance optimizations:

1. **Hash-based Change Detection**: Only imports data that has changed since the last import.
2. **Batch Processing**: Processes data in batches to reduce memory usage.
3. **API Retry Mechanism**: Automatically retries failed API calls with configurable backoff.

## Advanced Features

### Product Models and Variations

The connector fully supports Akeneo's product models and variants:

- Product models are imported as WooCommerce variable products
- Product variants are imported as WooCommerce product variations
- Attribute axes are properly mapped to WooCommerce variation attributes

### Multilingual Support

The connector supports multilingual content through integration with WPML or Polylang:

- Automatically creates translations for products, categories, and attributes
- Synchronizes translations between different language versions

## Troubleshooting

### Logging

The connector logs all import activities and errors to log files located in:

```
/path/to/wordpress/logs/akeneo_connector-debug.log
/path/to/wordpress/logs/akeneo_connector-error.log
```

### Common Issues

- **API Connection Issues**: Verify your Akeneo API credentials and ensure your server can reach the Akeneo instance.
- **Memory Limit Errors**: Increase PHP memory limit in your wp-config.php file.
- **Import Timeouts**: For large catalogs, consider importing data in smaller batches or increasing PHP execution time.

## Contributing

Contributions to the Akeneo Connector are welcome! Please feel free to submit pull requests or create issues for bugs and feature requests.

## License

The Akeneo Connector for WooCommerce is licensed under the MIT License.

## Credits

Developed by [Amphibee](https://amphibee.fr/) - Specialists in e-commerce and PIM integration.
