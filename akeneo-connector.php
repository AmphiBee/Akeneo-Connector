<?php
/*
Plugin Name: Akeneo Connector
Plugin URI: https://amphibee.fr
Description: Connect Akeneo with WooCommerce
Author: AmphiBee (hello@amphibee.fr)
Version: 0.0.5
Author URI: https://amphibee.fr
Text Domain: akeneo_connector
Domain Path: /languages
 */

if (PHP_VERSION_ID < 70200 || version_compare(get_bloginfo('version'), '5.2', '<')) {
    function akeneo_connector_compatability_warning()
    {
        echo '<div class="error"><p>' . sprintf(
                __(
                    '“%1$s” requires PHP %2$s (or newer) and WordPress %3$s (or newer) to function properly. Your site is using PHP %4$s and WordPress %5$s. Please upgrade. The plugin has been automatically deactivated.',
                    'akeneo_connector'
                ),
                __('Akeneo Connector', 'akeneo_connector'),
                '7.2',
                '5.2',
                PHP_VERSION,
                $GLOBALS['wp_version']
            ) . '</p></div>';
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }

    add_action('admin_notices', 'akeneo_connector_compatability_warning');

    function akeneo_connector_deactivate_self()
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }

    add_action('admin_init', 'akeneo_connector_deactivate_self');

    return;
}

use AmphiBee\AkeneoConnector\Plugin;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Plugin.php';

function akeneo_connector_get_instance(): Plugin
{
    return AmphiBee\AkeneoConnector\Plugin::getInstance(__FILE__);
}

akeneo_connector_get_instance();
