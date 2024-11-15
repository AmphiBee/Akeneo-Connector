<?php
namespace AmphiBee\AkeneoConnector\Admin;

use AmphiBee\AkeneoConnector\Blade;
use AmphiBee\AkeneoConnector\Plugin;
use AmphiBee\AkeneoConnector\Helpers\Translator;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @version    1.1.2
 * @access     public
 */
class Settings
{
    public static $akeneoSettings = [];

    public static $opt_page = 'configuration-akeneo-connector';

    public function __construct()
    {
        add_action('admin_menu', array( $this, 'akeneo_settings_add_plugin_page' ));
        add_action('admin_init', array( $this, 'akeneo_settings_page_init' ));

        add_filter('ak/settings/tabs/template', function ($template) {
            return $template;
        });
    }

    public function akeneo_settings_add_plugin_page()
    {
        add_options_page(
            'Configuration Akeneo Connector',               // page_title
            'Configuration Akeneo Connector',               // menu_title
            'manage_options',                               // capability
            static::$opt_page,                              // menu_slug
            [$this, 'akeneo_settings_create_admin_page']    // callback
        );
    }


    /**
     * Get Akeneo setting by key fi specified and exists, or get all settings.
     *
     * @return array|string
     */
    public static function getAkeneoSettings($key = false)
    {
        if (!isset(self::$akeneoSettings['synchronization'])) {
            self::$akeneoSettings['synchronization'] = get_option('akeneo_settings_synchronization') ?: [];
        }
        if (!isset(self::$akeneoSettings['credentials'])) {
            self::$akeneoSettings['credentials'] = get_option('akeneo_settings_credentials') ?: [];
        }

        if ($key) {
            $single_dimension = array_merge(...array_values(self::$akeneoSettings));

            if (array_key_exists($key, $single_dimension)) {
                return $single_dimension[$key];
            }

            return null;
        }

        return self::$akeneoSettings;
    }


    /**
     * Render plugin page using blade.
     *
     * @return void
     */
    public function akeneo_settings_create_admin_page()
    {
        $errors = Plugin::getErrorMessages();

        Blade::print('index', [
            'settings_options' => self::getAkeneoSettings(),
            'option_page'      => static::$opt_page,
            'base_url'         => static::getPluginBaseUrl(),
            'errors'           => $errors,
        ]);
    }


    /**
     * Initiate the settings page.
     * Register option groups & fields.
     *
     * @return void
     */
    public function akeneo_settings_page_init()
    {
        $this->registerSynchronizationSettings();
        $this->registerCredentialsSettings();
    }


    /**
     * Get the value for a given key from credential configurations.
     *
     * @return string
     */
    public static function getCredentialValue($key = ''): string
    {
        return self::getAkeneoSettings()['credentials'][$key] ?? '';
    }


    /**
     * Get the Mapping value for a given key from synchronization configurations.
     *
     * @return string
     */
    public static function getMappingValue($key = ''): string
    {
        return self::getAkeneoSettings()['synchronization']['attribute_mapping'][$key] ?? '';
    }


    /**
     * Get the Type value for a given key from synchronization configurations.
     *
     * @return string
     */
    public static function getTypeValue($key = ''): string
    {
        return self::getAkeneoSettings()['synchronization']['attribute_type'][$key] ?? '';
    }


    /**
     * Settings input sanatizer.
     *
     * @param array $input
     * @return mixed
     */
    public function akeneo_settings_sanitize(array $input)
    {
        $input  = array_filter($input);

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                array_walk($value, 'sanitize_text_field');
            }

            if (is_string($value)) {
                if ($key === 'akaneo_host') {
                    $value = esc_url_raw($value);
                    continue;
                }

                $value = sanitize_text_field($value);
            }

            $input[$key] = $value;
        }

        return array_filter($input);
    }


    public function akeneo_settings_section_info()
    {
    }


    /**
     * Get Akaneo attributes formatted to be used in synchronization settings.
     * Stored for 12 hours in a transient.
     *
     * @return array
     */
    public function getAkaneoAttributeSettings(): array
    {
        $settings_fields = get_transient('_akeneo_attr_settings') ?: [];

        if (empty($settings_fields)) {
            try {
                $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();

                foreach ($attributeDataProvider->getAll() as $AknAttr) {
                    $settings_fields[$AknAttr->getCode()] = [
                        'labels' => $AknAttr->getLabels(),
                        'code'   => $AknAttr->getCode()
                    ];
                }

                set_transient('_akeneo_attr_settings', $settings_fields, 12 * HOUR_IN_SECONDS);

                # May crash if we don't have credentials
            } catch (\Exception $e) {
                $settings_fields = [];
            }
        }

        return $settings_fields;
    }

    /**
     * Get the plugin page url
     *
     * @return string
     */
    public static function getPluginBaseUrl(): string
    {
        return (string) menu_page_url(static::$opt_page, false);
    }


    /**
     * Register options setting, for "Synchronization" tab.
     *
     * @return void
     */
    protected function registerSynchronizationSettings(): void
    {
        register_setting(
            'akeneo_settings_synchronization_group', // option_group
            'akeneo_settings_synchronization',       // option_name
            [$this, 'akeneo_settings_sanitize']      // sanitize_callback
        );

        add_settings_section(
            'akeneo_settings_synchronization_section',          // id
            __('Product synchronization', 'akeneo-connector'),  // title
            [$this, 'akeneo_settings_section_info'],            // callback
            'akeneo-settings-synchronization'                   // page
        );

        $settingsFlds = $this->getAkaneoAttributeSettings();
        $translator   = new Translator;

        foreach ($settingsFlds as $settingsFld) {
            $labels  = collect($settingsFld['labels'] ?: []);
            $current = $translator->current() ?: '';
            $primary = $translator->default ?: '';

            $attrName = ($labels->get($current) ?: $labels->get($primary)) ?: $labels->first();

            add_settings_field(
                "map_{$settingsFld['code']}}",              // id
                $attrName,                                  // title
                function () use ($settingsFld) {
                    $this->getMappingSelectField($settingsFld);
                },                                          // callback
                'akeneo-settings-synchronization',          // page
                'akeneo_settings_synchronization_section'   // section
            );
        }
    }


    /**
     * Register options setting, for "Synchronization" tab.
     *
     * @return void
     */
    protected function registerCredentialsSettings(): void
    {
        register_setting(
            'akeneo_settings_credentials_group',    // option_group
            'akeneo_settings_credentials',          // option_name
            [$this, 'akeneo_settings_sanitize']     // sanitize_callback
        );

        add_settings_section(
            'akeneo_settings_credential_section',          // id
            __('Akaneo credentials', 'akeneo-connector'),  // title
            [$this, 'akeneo_settings_section_info'],       // callback
            'akeneo-settings-credential'                   // page
        );

        $settings_flds = [
            'akaneo_host'          => __('Host', 'akeneo-connector'),
            'akaneo_client_id'     => __('Client ID', 'akeneo-connector'),
            'akaneo_client_secret' => __('Client secret', 'akeneo-connector'),
            'akaneo_user'          => __('User', 'akeneo-connector'),
            'akaneo_password'      => __('Password', 'akeneo-connector'),
            'akaneo_channel'       => __('Channel', 'akeneo-connector'),
        ];

        foreach ($settings_flds as $id => $name) {
            add_settings_field(
                $id,    // id
                $name,  // title
                function () use ($id, $name) {
                    $this->getTextField($id, $name, 'akeneo_settings_credentials');
                },  // callback
                'akeneo-settings-credential',          // page
                'akeneo_settings_credential_section'   // section
            );
        }
    }



    /**
     *
     */
    public function getTextField(string $id, string $name, string $option_name)
    {
        $value = '';

        Blade::print('fields.text', compact('id', 'name', 'option_name', 'value'));
    }


    /**
     *
     */
    public function getMappingSelectField($attrName)
    {
        // TODO: implements custom taxonomies
        // $taxonomies = get_object_taxonomies('product');
        // $taxonomies;

        $default = __('--- Select an option ---', 'akeneo-connector');

        $options = [
            # Post properties
            __('WP_Post properties', 'akeneo-connector') => [
                'post_title'         => __('Product title', 'akeneo-connector'),
                'post_excerpt'       => __('Short description', 'akeneo-connector'),
                'post_content'       => __('Main description', 'akeneo-connector'),
                'post_meta'          => __('Post meta', 'akeneo-connector'),
            ],
            # WooCommerce
            __('WooCommerce properties', 'akeneo-connector') => [
                'featured'           => __('Featured', 'akeneo-connector'),
                'ugs'                => __('Product identifier (UGS)', 'akeneo-connector'),
                'weight'             => __('Weight', 'akeneo-connector'),
                'regular_price'      => __('Price', 'akeneo-connector'),
                'sale_price'         => __('Sale Price', 'akeneo-connector'),
                'sale_from'          => __('Sale Price from', 'akeneo-connector'),
                'sale_to'            => __('Sale Price to', 'akeneo-connector'),
                'tax_status'         => __('Tax status', 'akeneo-connector'),
                'tax_class'          => __('Tax class', 'akeneo-connector'),
                'text_attribute'     => __('Text Attribute', 'akeneo-connector'),
                'global_attribute'   => __('Global Attribute', 'akeneo-connector'),
                'private_text_attribute'     => __('Text Attribute (private)', 'akeneo-connector'),
                'private_global_attribute'   => __('Global Attribute (private)', 'akeneo-connector'),
            ],
            # Taxonomies
            __('Product taxonomies', 'akeneo-connector') => [
                'product_cat'        => __('Product category', 'akeneo-connector'),
                'product_tag'        => __('Product tag', 'akeneo-connector'),
            ],
            # Post Media
            __('Medias', 'akeneo-connector') => [
                'post_thumbnail'     => __('Product thumbnail', 'akeneo-connector'),
                'gallery'            => __('Gallery', 'akeneo-connector'),
                'external_thumbnail' => __('External Product thumbnail (DAM)', 'akeneo-connector'),
                'external_media'     => __('External Media (DAM)', 'akeneo-connector'),
                'external_gallery'   => __('External Gallery (DAM)', 'akeneo-connector'),
            ],
        ];

        Blade::print('fields.mapping-select', [
           'attribute_code' => $attrName['code'],
           'default'        => $default,
           'options'        => $options,
           'option_name'    => 'akeneo_settings_synchronization',
        ]);
    }
}
