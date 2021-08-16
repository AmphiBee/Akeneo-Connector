<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AK_Attribute;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Option as AK_Option;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Tag as WP_Tag;

class TagAdapter
{
    public const ROOT_CATEGORY = 'root_category';

    /**
     * @param AK_Attribute $ak_attribute
     *
     * @return WP_Tag
     */
    public function getWordpressTagFromAttribute(AK_Attribute $ak_attribute): WP_Tag
    {
        return $this->fromAttrLikeItem($ak_attribute);
    }


    /**
     * @param AK_Option $ak_option
     *
     * @return WP_Tag
     */
    public function getWordpressTagFromOption(AK_Option $ak_option): WP_Tag
    {
        return $this->fromAttrLikeItem($ak_option);
    }


    /**
     * @param AK_Attribute|AK_Option $item
     *
     * @return WP_Tag
     */
    private function fromAttrLikeItem($item): WP_Tag
    {
        $tag = new WP_Tag();
        $tag->setName($item->getCode());
        $tag->setParent(self::ROOT_CATEGORY);
        $tag->setLabels($item->getLabels());

        return $tag;
    }
}
