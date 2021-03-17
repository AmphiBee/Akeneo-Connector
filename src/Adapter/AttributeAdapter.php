<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AkeneoAttribute;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute as WooCommerceAttribute;

class AttributeAdapter extends AbstractAdapter
{
    /**
     * @param AkeneoAttribute $akeneoAttribute
     *
     * @return WooCommerceAttribute
     */
    public function getWordpressAttribute(AkeneoAttribute $akeneoAttribute): WooCommerceAttribute
    {
        $attribute = new WooCommerceAttribute($akeneoAttribute->getCode());

        $attribute->setName($this->getLocalizedLabel($akeneoAttribute));
        $attribute->setType($akeneoAttribute->getType());

        return $attribute;
    }
}
