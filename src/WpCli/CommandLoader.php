<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use WP_CLI;

class CommandLoader
{
    public function __construct()
    {
        add_action('cli_init', [$this, 'registerCommands']);
    }

    public function registerCommands(): void
    {
        WP_CLI::add_command('akeneo categories', CategoryCommand::class);
        WP_CLI::add_command('akeneo option', AttributeOptionCommand::class);
        WP_CLI::add_command('akeneo models', ProductModelCommand::class);
        WP_CLI::add_command('akeneo products', ProductCommand::class);
    }
}
