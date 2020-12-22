<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use WP_CLI;

class Loader
{
    public function __construct()
    {
        add_action('cli_init', [$this, 'registerCommands']);
    }

    /**
    * Register command
    *
    * @access public
    * @since  1.0.0
    * @author AmphiBee
    */
    public function registerCommands()
    {
        WP_CLI::add_command('akeneo attributes', ProductAttribute::class);
        WP_CLI::add_command('akeneo products', Product::class);
        WP_CLI::add_command('akeneo product-categories', ProductCategory::class);
    }
}
