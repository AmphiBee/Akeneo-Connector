<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

class Tag extends Taxonomy implements WooCommerceEntityInterface
{
    /**
     * Category constructor.
     * @param string $name
     */
    public function __construct()
    {
        return parent::__construct('product_tag');
    }
}
