<?php

namespace AmphiBee\AkeneoConnector;

use OP\Support\Facades\ObjectPress;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\WpCli\CommandLoader;

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
class Plugin
{
    private static ?Plugin $instance = null;
    public string $name;
    public string $prefix;
    public string $version;
    public string $file;

    protected static array $errors = [];

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
            self::$instance = new self($file);
        }

        return self::$instance;
    }


    private function __construct(string $file)
    {
        if (!function_exists('get_plugin_data')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $data = get_plugin_data($file);

        $this->name = $data['Name'];
        $this->prefix = 'akeneo_connector';
        $this->version = $data['Version'];
        $this->file = $file;

        $this->run();
    }


    /**
     * Execution function which is called after the class has been initialized.
     * This contains hook and filter assignments, etc.
     *
     * @return void
     */
    private function run()
    {
        add_action('plugins_loaded', array($this, 'bootObjectPress'));
        add_action('plugins_loaded', array($this, 'loadPluginTextdomain'));

        if (is_admin()) {
            new Settings();
        }

        new CommandLoader();
    }


    /**
     * Load translation files from the indicated directory.
     *
     * @return void
     */
    public function loadPluginTextdomain(): void
    {
        load_plugin_textdomain(
            'akeneo_connector',
            false,
            sprintf('%s/languages', dirname(plugin_basename($this->file)))
        );
    }


    /**
     * Boot ObjectPress library
     *
     * @return void
     */
    public function bootObjectPress(): void
    {
        ObjectPress::boot(__DIR__ . '/../config');
    }


    /**
     * Append an error message to display on plugin admin page
     *
     * @return void
     */
    public static function addErrorMessage($message)
    {
        static::$errors[] = sprintf('Akeneo Connector : %s', $message);
    }

    /**
     * Get error message to display on plugin admin page
     *
     * @return array
     */
    public static function getErrorMessages(): array
    {
        return static::$errors;
    }
}
