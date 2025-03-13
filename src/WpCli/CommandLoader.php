<?php

namespace AmphiBee\AkeneoConnector\WpCli;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class CommandLoader
{
    public function __construct()
    {
        add_action('cli_init', [$this, 'registerCommands']);
    }

    public function registerCommands(): void
    {
        # run `wp akeneo help` to see commands usage.

        CategoryCommand::register();
        AttributeCommand::register();
        AttributeOptionCommand::register();
        ProductModelCommand::register();
        ProductCommand::register();

        # Migration commands
        MigrateTranslationsCommand::register();
        MigrateHashColumnCommand::register();
    }
}
