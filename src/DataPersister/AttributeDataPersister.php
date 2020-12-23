<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AttributeDataPersister extends AbstractDataPersister
{
    /**
     * @param Attribute $category
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateAttribute(Attribute $category): void
    {
        try {
            $attrAsArray = $this->getSerializer()->normalize($category);
            //@todo save in WC
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Attribute (Attr Code %s) %s',
                print_r($category, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
