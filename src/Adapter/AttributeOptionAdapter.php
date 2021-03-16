<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Option as AkeneoOption;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Option as WooCommerceOption;

class AttributeOptionAdapter extends AbstractAdapter
{
    /**
     * @param AkeneoOption $akeneoOption
     *
     * @return WooCommerceOption
     */
    public function getWordpressAttributeOption(AkeneoOption $akeneoOption): WooCommerceOption
    {
        $option = new WooCommerceOption($akeneoOption->getCode());
        $option->setName($this->getLocalizedLabel($akeneoOption));
        $option->setAttribute($akeneoOption->getAttribute());

        return $option;
    }
}
