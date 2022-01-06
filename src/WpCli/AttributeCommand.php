<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
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
class AttributeCommand extends AbstractCommand
{
    public static string $name = 'attributes';

    public static string $desc = 'Supports Akaneo Attributes import';

    public static string $long_desc = '';


    /**
     * Run the import command.
     */
    public function import(): void
    {
        # Debug
        $this->print('Starting attributes import');

        $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();
        $attributeAdapter      = new AttributeAdapter();
        $attrPersister         = new AttributeDataPersister();

        do_action('ak/attributes/before_import', $attributeDataProvider->getAll());

        $attribute_data = (array) apply_filters('ak/attributes/import_data', iterator_to_array($attributeDataProvider->getAll()));

        /**
         * @var Attribute $AknAttr
         */
        foreach ($attribute_data as $AknAttr) {
            $this->print(sprintf('Running AttrCode: %s', $AknAttr->getCode()));

            $wc_attribute = $attributeAdapter->fromAttribute($AknAttr, $this->translator->default);
            $attrPersister->importBooleanAttributeOption($wc_attribute);
        }

        do_action('ak/attributes/after_import', $attributeDataProvider->getAll());
    }
}
