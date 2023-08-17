<?php

namespace AmphiBee\AkeneoConnector;

use OP\Support\Facades\ObjectPress;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\WpCli\CommandLoader;

/**
 * This file is part of the Amphibee package.
 *
 * @package    amphibee/akeneo-connector
 * @license    MIT
 * @author     AmphiBee <hello@amphibee.fr>
 * @author     tgeorgel <thomas@hydrat.agency>
 * @copyright  (c) AmphiBee <hello@amphibee.fr>
 * @since      1.1.0
 * @version    1.13.0
 */
class Plugin
{
    private static ?Plugin $instance = null;
    public string $name;
    public string $prefix;
    public string $version;
    public string $file;

    protected static array $errors = [];

    protected const DB_VERSION = '1.13.0';

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

        $this->name    = $data['Name'];
        $this->prefix  = 'akeneo_connector';
        $this->version = $data['Version'];
        $this->file    = $file;

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
        add_action('plugins_loaded', array($this, 'loadPluginTextdomain'));
        add_filter('plugin_action_links_akeneo-connector/akeneo-connector.php', array($this, 'settingsLinks'));

        $this->bootObjectPress();
        $this->migrateDatabase();

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
    private function bootObjectPress(): void
    {
        ObjectPress::boot(__DIR__ . '/../config');
    }


    /**
     * Add the "settings" link into the plugin list view.
     *
     * @param array $links The plugin links
     * @return array
     */
    public function settingsLinks($links)
    {
        // Build and escape the URL.
        $url = esc_url(add_query_arg(
            'page',
            'configuration-akeneo-connector',
            get_admin_url() . 'options-general.php'
        ));

        // Create the link.
        $settings_link = "<a href='$url'>" . __('Settings') . '</a>';

        // Adds the link to the end of the array.
        array_push(
            $links,
            $settings_link
        );

        return $links;
    }

    /**
     * Bootstrap the database migrations.
     *
     * @return void
     */
    private function migrateDatabase()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        # We're not up to date on database migrations
        if (get_option('akeneo_connector_db_version') !== static::DB_VERSION) {
            /**
             * Creates the `products_models` table
             */
            $table = $wpdb->prefix . 'akconnector_products_models';

            $sql = "CREATE TABLE IF NOT EXISTS $table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                product_id mediumint(9) DEFAULT NULL,
                parent_id mediumint(9) DEFAULT NULL,
                model_code varchar(45) DEFAULT NULL,
                variant_code varchar(45) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at datetime DEFAULT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

            dbDelta($sql);

            # Inform wp we're on the last database version
            update_option('akeneo_connector_db_version', static::DB_VERSION);
        }
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
