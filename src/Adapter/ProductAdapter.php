<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Product as AK_Product;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product as WP_Product;

class ProductAdapter
{
    /**
     * Creates a WP Product from an Akeneo Product.
     */
    public function fromProduct(AK_Product $ak_product): WP_Product
    {
        $product = new WP_Product($ak_product->getIdentifier());

        $product->setEnabled($ak_product->isEnabled());
        $product->setParent($ak_product->getParent());
        $product->setValues($ak_product->getValues());
        $product->setCategories($ak_product->getCategories());
        $product->setAssociation($ak_product->getAssociations());

        return $product;
    }

    /**
     * @param AK_Product $ak_product
     *
     * @return WP_Product
     */
    public function getWordpressProduct(AK_Product $ak_product): WP_Product
    {
        return $this->fromProduct($ak_product);
    }
}
