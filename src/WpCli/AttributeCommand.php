<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\AttributeAdapter;
use AmphiBee\AkeneoConnector\DataPersister\AttributeDataPersister;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use WP_CLI;

class AttributeCommand
{
    public function import(): void
    {
        $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();
        $attributeAdapter = new AttributeAdapter();
        $attrPersister = new AttributeDataPersister();

        /** @var Attribute $AknAttr */
        foreach ($attributeDataProvider->getAll() as $AknAttr) {
            LoggerService::log(Logger::DEBUG, sprintf('Running AttrCode: %s', $AknAttr->getCode()));
            $wooCommerceAttribute = $attributeAdapter->getWordpressAttribute($AknAttr);
            $attrPersister->importBooleanAttributeOption($wooCommerceAttribute);

            WP_CLI::line($AknAttr->getCode());
        }
    }
}
