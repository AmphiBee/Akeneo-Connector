<?php
/**
 * This file is part of the Amphibee package.
 *
 * @package    amphibee/akeneo-connector
 * @version    1.15.3
 * @license    MIT
 * @author     AmphiBee <hello@amphibee.fr>
 * @author     tgeorgel <thomas@hydrat.agency>
 * @copyright  (c) AmphiBee <hello@amphibee.fr>
 */

/*
Plugin Name: Akeneo Connector
Plugin URI: https://amphibee.fr
Description: Connect Akeneo with WooCommerce. The plugin uses the Akeneo api to import Akeneo PIM data (attributes, products, variations..).
Author: AmphiBee (hello@amphibee.fr)
Version: 1.15.3
Author URI: https://amphibee.fr
Text Domain: akeneo_connector
Domain Path: /languages
*/

# Make sure we're in the wordpress lifecycle
defined('ABSPATH') || die("Cheatin’ uh?");

# Compatibility check
if (PHP_VERSION_ID < 70200 || version_compare(get_bloginfo('version'), '5.2', '<')) {
    add_action('admin_notices', 'akeneo_connector_compatability_warning');
    add_action('admin_init', 'akeneo_connector_deactivate_self');
    return;
}

/**
 * Display a compatibility error message and prevent plugin activation.
 *
 * @return void
 */
function akeneo_connector_compatability_warning()
{
    $message = sprintf(
        __(
            '“%1$s” requires PHP %2$s (or newer) and WordPress %3$s (or newer) to function properly. Your site is using PHP %4$s and WordPress %5$s. Please upgrade. The plugin has been automatically deactivated.',
            'akeneo_connector'
        ),
        __('Akeneo Connector', 'akeneo_connector'),
        '7.2',
        '5.2',
        PHP_VERSION,
        $GLOBALS['wp_version']
    );

    printf('<div class="error"><p>%s</p></div>', $message);

    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

/**
 * Disable our plugin.
 *
 * @return void
 */
function akeneo_connector_deactivate_self()
{
    deactivate_plugins(plugin_basename(__FILE__));
}


/**
 * Boot the plugin.
 */
use AmphiBee\AkeneoConnector\Plugin;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Plugin.php';

function akeneo_connector_get_instance(): Plugin
{
    return Plugin::getInstance(__FILE__);
}

akeneo_connector_get_instance();
