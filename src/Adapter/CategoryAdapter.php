<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AK_Category;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category as WP_Category;

class CategoryAdapter
{
    /**
     * Adapt a Akeneo category into a Woocommerce catagory
     *
     * @param   AK_Category $ak_category
     * @return  WP_Category
     */
    public function fromCategory(AK_Category $ak_category): WP_Category
    {
        $wp_category = new WP_Category();

        $wp_category->setName($ak_category->getCode());
        $wp_category->setParent($ak_category->getParent());
        $wp_category->setLabels($ak_category->getLabels());

        $wp_category->setDescription($ak_category->getDescription());
        $wp_category->setDescriptionEN($ak_category->getDescriptionEN());
        $wp_category->setCategoryContentText($ak_category->getCategoryContentText());
        $wp_category->setCategoryContentTextEN($ak_category->getCategoryContentTextEN());
        $wp_category->setCategoryContentImage($ak_category->getCategoryContentImage());
        $wp_category->setMiniature($ak_category->getMiniature());
        $wp_category->setMetaDatas($ak_category->getMetaDatas());

        return $wp_category;
    }


    /**
     * @deprecated use fromCategory() instead
     */
    public function getWordpressCategory(AK_Category $ak_ategory): WP_Category
    {
        return $this->fromCategory($ak_ategory);
    }
}
