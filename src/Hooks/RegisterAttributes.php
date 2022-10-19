<?php

namespace AmphiBee\AkeneoConnector\Hooks;

use OP\Framework\Wordpress\Hook;
use AmphiBee\AkeneoConnector\Plugin;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\Helpers\Translator;
use AmphiBee\AkeneoConnector\Facade\LocaleStrings;
use AmphiBee\AkeneoConnector\Adapter\AttributeAdapter;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\DataPersister\AttributeDataPersister;

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
class RegisterAttributes extends Hook
{
    /**
     * Event name to hook on.
     */
    public $hook = 'admin_init';


    /**
     * What to do.
     */
    public function execute()
    {
        # Don't run on CLI
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        $taxonomies = get_transient('_ak_attributes');
        $persister  = new AttributeDataPersister();

        # If no transient found, get data from API
        if ($taxonomies == false && Settings::getCredentialValue('akaneo_host')) {
            $provider   = AkeneoClientBuilder::create()->getAttributeProvider();
            $adapter    = new AttributeAdapter();
            $translator = new Translator();
            $taxonomies = [];

            try {
                foreach ($provider->getAll() as $ak_attribute) {
                    $attr_langs        = array_keys($ak_attribute->getLabels());
                    $available_locales = array_intersect($attr_langs, $translator->available);
                    $primary           = in_array($translator->default, $available_locales) ? $translator->default : $available_locales[0];

                    $wp_attribute = $adapter->fromAttribute($ak_attribute, $primary);

                    $tax_object = [
                        'code'   => $wp_attribute->getCode(),
                        'name'   => $wp_attribute->getName(),
                        'type'   => $wp_attribute->getType(),
                    ];

                    # As we can't translate a taxonomy, we're registering the translation string
                    LocaleStrings::register($tax_object['code'], $tax_object['name']);

                    # Then, save all corresponding translations
                    foreach ($available_locales as $locale) {
                        if ($locale !== $primary) {
                            LocaleStrings::translate($tax_object['name'], $ak_attribute->getLabels()[$locale], $locale);
                        }
                    }

                    $taxonomies[] = $tax_object;
                }


                # Save strings in db
                LocaleStrings::commit();

                set_transient('_ak_attributes', $taxonomies, 12 * HOUR_IN_SECONDS);

                # Catch error
            } catch (\Exception $e) {
                $message = $e->getMessage();

                # The Host is not set or is not valid
                if (strpos($message, 'cURL error 3') === 0) {
                    Plugin::addErrorMessage('The specified host is not valid.');
                }
                # Other error
                else {
                    Plugin::addErrorMessage(sprintf('An error occured while retreiving attributes : %s', $message));
                }
            }
        }

        # Create/Update attributes
        if (is_array($taxonomies) && !empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $persister->createOrUpdateFromArray($taxonomy);
            }
        }
    }
}
