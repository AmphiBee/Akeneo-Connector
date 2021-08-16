<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\AttributeOptionAdapter;
use AmphiBee\AkeneoConnector\DataPersister\OptionDataPersister;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AK_Attribute;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;

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
class AttributeOptionCommand extends AbstractCommand
{
    public static string $name = 'options';

    public static string $desc = 'Supports Akaneo Attribute Options import';

    public static string $long_desc = '';

    private const ALLOWED_TYPES = [
        'pim_catalog_simpleselect',
        'pim_catalog_multiselect',
        'pim_reference_data_simpleselect',
        'pim_reference_data_multiselect',
    ];

    /**
     * Run the import command.
     */
    public function import(): void
    {
        # Debug
        $this->print('Starting options import');

        $provider = AkeneoClientBuilder::create()->getAttributeProvider();

        do_action('ak/a/options/before_import', $provider->getAll());

        $attribute_data = apply_filters('ak/f/options/import_data', iterator_to_array($provider->getAll()));

        foreach ($attribute_data as $ak_attribute) {
            if (!in_array($ak_attribute->getType(), static::ALLOWED_TYPES)) {
                continue;
            }

            $this->print(sprintf('Running Options for Attribute with code: %s', $ak_attribute->getCode()));

            if (preg_match('/^pim_reference_data_/', $ak_attribute->getType())) {
                $this->importReferenceDataOptions($ak_attribute);
            } else {
                $this->importAttributeOptions($ak_attribute);
            }
        }

        do_action('ak/a/options/after_import', $provider->getAll());
    }


    /**
     * Get single attribute options and sync it into database
     *
     * @return void
     */
    public function importAttributeOptions(AK_Attribute $ak_attribute): void
    {
        $code      = $ak_attribute->getCode();
        $prodiver  = AkeneoClientBuilder::create()->getAttributeOptionProvider();
        $adapter   = new AttributeOptionAdapter();
        $persister = new OptionDataPersister();

        $attribute_options_data = $prodiver->getAll($code);

        do_action("ak/a/options/before_import/attr={$code}", $attribute_options_data);

        $attribute_options_data = apply_filters("ak/f/options/import_data/attr={$code}", iterator_to_array($attribute_options_data));

        # import attribute options
        foreach ($attribute_options_data as $ak_option) {
            $this->log(sprintf('Running AttrOptionCode: %s', $ak_option->getCode()));

            $wp_option = $adapter->fromOption($ak_option);
            $persister->createOrUpdate($wp_option);
        }

        do_action("ak/a/options/after_import/attr={$code}", $prodiver);
    }


    /**
     * Get single attribute reference datas and sync it into database
     *
     * @return void
     */
    public function importReferenceDataOptions(AK_Attribute $ak_attribute): void
    {
        $name      = $ak_attribute->getMetaDatas()['reference_data_name'] ?? '';
        $prodiver  = AkeneoClientBuilder::create()->getCustomReferenceDataProvider();
        $adapter   = new AttributeOptionAdapter();
        $persister = new OptionDataPersister();

        if (!$name) {
            $this->error(sprintf('Could not find `%s` meta_data for attribute `%s`.', 'reference_data_name', $ak_attribute->getCode()));
            return;
        }

        $attribute_options_data = $prodiver->getAll($name);

        do_action("ak/a/options/before_import/refdata={$name}", $attribute_options_data);

        $attribute_options_data = apply_filters("ak/f/options/import_data/refdata={$name}", iterator_to_array($attribute_options_data));

        # import attribute options
        foreach ($attribute_options_data as $ak_option) {
            $this->log(sprintf('Running AttrOptionCode: %s', $ak_option->getCode()));

            $wp_option = $adapter->fromCustomReferenceData($ak_option, $ak_attribute->getCode());
            $persister->createOrUpdate($wp_option);
        }

        do_action("ak/a/options/after_import/refdata={$name}", $prodiver);
    }
}
