<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Adapter\ProductAdapter;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ProductDataPersister extends AbstractDataPersister
{

    /**
     * @param Product $product
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateProduct(Product $product): void
    {
        try {
            //$productAsArray = $this->getSerializer()->normalize($product);
            $productCode = $product->getCode();
            $productAsArray = $this->getSerializer()->normalize($product);
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Product (ModelCode %s) %s',
                print_r($product, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
