<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector;

use AmphiBee\AkeneoConnector\WpCli\CommandLoader;

class Plugin
{
    private static ?Plugin $instance = null;
    public string $name;
    public string $prefix;
    public string $version;
    public string $file;

    /**
     * Creates an instance if one isn't already available,
     * then return the current instance.
     *
     * @param string $file The file from which the class is being instantiated.
     *
     * @return Plugin The class instance.
     */
    public static function getInstance(string $file): Plugin
    {
        if (!isset(self::$instance) && !(self::$instance instanceof self)) {
            self::$instance = new self;

            if (!function_exists('get_plugin_data')) {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }

            $data = get_plugin_data($file);

            self::$instance->name = $data['Name'];
            self::$instance->prefix = 'akeneo_connector';
            self::$instance->version = $data['Version'];
            self::$instance->file = $file;

            self::$instance->run();
        }

        return self::$instance;
    }

    /**
     * Execution function which is called after the class has been initialized.
     * This contains hook and filter assignments, etc.
     */
    private function run()
    {
        add_action('plugins_loaded', array($this, 'loadPluginTextdomain'));

        new CommandLoader();
    }

    /**
     * Load translation files from the indicated directory.
     */
    public function loadPluginTextdomain(): void
    {
        load_plugin_textdomain(
            'akeneo_connector',
            false,
            sprintf('%s/languages', dirname(plugin_basename($this->file)))
        );
    }
}
