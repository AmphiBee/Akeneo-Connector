<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

use OP\Lib\WpEloquent\Model\Term;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;

class Category extends Taxonomy implements WooCommerceEntityInterface
{
    /**
     * Category constructor.
     * @param string $name
     */
    public function __construct(string $name = '')
    {
        parent::__construct('product_cat');

        $this->setName($name);
    }
}
