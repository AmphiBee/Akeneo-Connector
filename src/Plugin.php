<?php

namespace AmphiBee\AkeneoConnector;

use AmphiBee\AkeneoConnector\WpCli\Loader;

class Plugin
{
    private static $instance;
    public $name = '';
    public $prefix = '';
    public $version = '';
    public $file = '';

    /**
     * Creates an instance if one isn't already available,
     * then return the current instance.
     *
     * @param string $file The file from which the class is being instantiated.
     * @return object       The class instance.
     */
    public static function getInstance($file)
    {
        if (!isset(self::$instance) && !(self::$instance instanceof Plugin)) {
            self::$instance = new Plugin;

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

        new Loader();
    }

    /**
     * Load translation files from the indicated directory.
     */
    public function loadPluginTextdomain()
    {
        load_plugin_textdomain('akeneo_connector', false, dirname(plugin_basename($this->file)) . '/languages');
    }
}
