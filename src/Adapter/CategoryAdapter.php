<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AkeneoCategory;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category as WooCommerceCategory;

class CategoryAdapter
{
    public const ROOT_CATEGORY = 'root_category';

    /**
     * @param AkeneoCategory $akeneoCategory
     *
     * @return WooCommerceCategory
     */
    public function getWordpressCategory(AkeneoCategory $akeneoCategory): WooCommerceCategory
    {
        $category = new WooCommerceCategory();
        $category->setName($akeneoCategory->getCode());
        $category->setParent($akeneoCategory->getParent() ?? self::ROOT_CATEGORY);

        //@todo adapt others needed things

        return $category;
    }
}
