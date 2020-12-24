<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\AttributeOptionAdapter;
use AmphiBee\AkeneoConnector\DataPersister\OptionDataPersister;
use AmphiBee\AkeneoConnector\DataProvider\AttributeOptionDataProvider;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use WP_CLI;

class AttributeOptionCommand
{
    private ?AttributeOptionDataProvider $AttributeOptionDataProvider = null;
    private ?AttributeOptionAdapter $attributeOptionAdapter = null;
    private ?OptionDataPersister $optionDataPersister = null;

    private const ALLOWED_TYPES = ['pim_catalog_simpleselect', 'pim_catalog_multiselect'];

    public function import(): void
    {
        $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();

        /** @var Attribute $aknAttr */
        foreach ($attributeDataProvider->getAll() as $aknAttr) {
            if (!in_array($aknAttr->getType(), self::ALLOWED_TYPES)) {
                continue;
            }

            LoggerService::log(Logger::DEBUG, sprintf('Running Options for AttrCode: %s', $aknAttr->getCode()));
            WP_CLI::warning(sprintf('Running Options for AttrCode: %s', $aknAttr->getCode()));

            $this->getAttrsOptions($aknAttr);
        }
    }

    public function getAttrsOptions(Attribute $attribute): void
    {
        /** @var Attribute $AknAttr */
        foreach ($this->getAttributeOptionDataProvider()->getAll($attribute->getCode()) as $AknAttrOption) {
            LoggerService::log(Logger::DEBUG, sprintf('Running AttrOptionCode: %s', $AknAttrOption->getCode()));
            WP_CLI::warning(sprintf('Running Option Code: %s', $AknAttrOption->getCode()));

            $wooCommerceAttrOption = $this->getAttributeOptionAdapter()->getWordpressAttributeOption($AknAttrOption);
            $this->getOptionDataPersister()->createOrUpdateOption($wooCommerceAttrOption);
        }
    }

    /**
     * @return AttributeOptionDataProvider
     */
    private function getAttributeOptionDataProvider(): AttributeOptionDataProvider
    {
        if (!$this->AttributeOptionDataProvider) {
            $this->AttributeOptionDataProvider = AkeneoClientBuilder::create()->getAttributeOptionProvider();
        }

        return $this->AttributeOptionDataProvider;
    }

    /**
     * @return AttributeOptionAdapter
     */
    private function getAttributeOptionAdapter(): AttributeOptionAdapter
    {
        if (!$this->attributeOptionAdapter) {
            $this->attributeOptionAdapter = new AttributeOptionAdapter();
        }

        return $this->attributeOptionAdapter;
    }

    /**
     * @return OptionDataPersister
     */
    private function getOptionDataPersister(): OptionDataPersister
    {
        if (!$this->optionDataPersister) {
            $this->optionDataPersister = new OptionDataPersister();
        }

        return $this->optionDataPersister;
    }
}
