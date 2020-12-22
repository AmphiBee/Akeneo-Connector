<?php declare(strict_types=1);

/**
 * This file is part of the Adexos package.
 * (c) Adexos <contact@adexos.fr>
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

        return $category;
    }
}
