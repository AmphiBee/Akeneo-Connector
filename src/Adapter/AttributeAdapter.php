<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AK_Attribute;
use AmphiBee\AkeneoConnector\Entity\Akeneo\CustomReferenceData as AK_CustomReferenceData;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute as WP_Attribute;

class AttributeAdapter extends AbstractAdapter
{
    /**
     * Creates a WooCommerceAttribute from an AkeneoAttribute.
     *
     * @return WP_Attribute
     */
    public function fromAttribute(AK_Attribute $akeneoAttribute, $locale = 'en_US'): WP_Attribute
    {
        $attribute = new WP_Attribute($akeneoAttribute->getCode());

        $attribute->setName($this->getLocalizedLabel($akeneoAttribute, $locale));
        $attribute->setType($akeneoAttribute->getType());

        return $attribute;
    }


    /**
     * Creates a WooCommerceAttribute from an AkeneoAttribute.
     *
     * @return WP_Attribute
     */
    public function fromCustomReferenceData(AK_CustomReferenceData $reference_data, $locale = 'en_US'): WP_Attribute
    {
        $attribute = new WP_Attribute($reference_data->getCode());

        $attribute->setName($this->getLocalizedLabel($reference_data, $locale));
        $attribute->setType($reference_data->getType());

        return $attribute;
    }


    /**
     * Creates a WooCommerceAttribute from an AkeneoAttribute.
     *
     * @return WP_Attribute
     */
    public function fromArray(array $data): WP_Attribute
    {
        $attribute = new WP_Attribute(
            $data['code'],
            $data['name'],
            $data['type']
        );

        return $attribute;
    }


    /**
     * @deprecated use fromAttribute() instead
     */
    public function getWordpressAttribute(AK_Attribute $akeneoAttribute, $locale = 'en_US'): WP_Attribute
    {
        return $this->fromAttribute($akeneoAttribute, $locale);
    }
}
