<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Product as AkeneoProduct;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product as WooCommerceProduct;

class ProductAdapter
{
    /**
     * @param AkeneoProduct $akeneoProduct
     *
     * @return WooCommerceProduct
     */
    public function getWordpressProduct(AkeneoProduct $akeneoProduct): WooCommerceProduct
    {
        $product = new WooCommerceProduct($akeneoProduct->getIdentifier());
        $product->setEnabled($akeneoProduct->isEnabled());
        $product->setParent($akeneoProduct->getParent());
        $product->setValues($akeneoProduct->getValues());
        $product->setCategories($akeneoProduct->getCategories());
        $product->setAssociation($akeneoProduct->getAssociations());

        return $product;
    }
}
