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
     * @param Attribute $attribute
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @todo remove suppress warning
     */
    public function createOrUpdateAttribute(Attribute $attribute): void
    {
        try {
            $attrAsArray = $this->getSerializer()->normalize($attribute);
            //@todo save in WC
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Attribute (Attr Code %s) %s',
                print_r($attribute, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
