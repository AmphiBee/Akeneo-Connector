<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\CustomReferenceData as AK_CustomReferenceData;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Option as AK_Option;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Option as WP_Option;

class AttributeOptionAdapter extends AbstractAdapter
{
    /**
     * Creates a WooCommerce Option from an Akeneo Option.
     *
     * @return WP_Option
     */
    public function fromOption(AK_Option $ak_option): WP_Option
    {
        $wp_option = new WP_Option($ak_option->getCode());

        $wp_option->setAttribute($ak_option->getAttribute())
                ->setLabels($ak_option->getLabels());

        return $wp_option;
    }


    /**
     * Creates a WooCommerce Option from an Akeneo CustomReferenceData.
     *
     * @param string $attribute_code The linked attribute. Needed for mapping values !
     *
     * @return WP_Option
     */
    public function fromCustomReferenceData(AK_CustomReferenceData $ref_data, string $attribute_code = ''): WP_Option
    {
        $wp_option = new WP_Option($ref_data->getCode());

        $wp_option->setAttribute($attribute_code ?: $ref_data->getType())
                ->setLabels($ref_data->getLabels())
                ->setReferenceData($ref_data->getType())
                ->setMetaDatas($ref_data->getMetaDatas());

        return $wp_option;
    }


    /**
     * @deprecated use fromOption() instead
     */
    public function getWordpressAttributeOption(AK_Option $akeneoOption): WP_Option
    {
        return $this->fromOption($akeneoOption);
    }
}
