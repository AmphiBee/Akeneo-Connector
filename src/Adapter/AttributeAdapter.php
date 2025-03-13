<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Entity\Akeneo\CustomReferenceData as AK_CustomReferenceData;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute as WP_Attribute;

class AttributeAdapter extends AbstractAdapter
{
    /**
     * Génère un hash unique basé sur les données de l'attribut Akeneo
     * 
     * @param Attribute $attribute L'attribut Akeneo
     * @return string Le hash généré
     */
    protected function generateAttributeHash(Attribute $attribute): string
    {
        $hashData = [
            'code' => $attribute->getCode(),
            'type' => $attribute->getType(),
            'group' => $attribute->getGroup(),
            'localizable' => $attribute->isLocalizable(),
            'labels' => $attribute->getLabels(),
            'groupLabels' => $attribute->getGroupLabels(),
            'metaDatas' => $attribute->getMetaDatas()
        ];
        
        // Convertir en JSON puis générer un hash MD5
        return md5(json_encode($hashData));
    }

    /**
     * Creates a WP Attribute from an Akeneo Attribute.
     */
    public function fromAttribute(Attribute $attribute, string $locale): WP_Attribute
    {
        $wp_attribute = new WP_Attribute();

        $wp_attribute->setCode($attribute->getCode());
        $wp_attribute->setType($attribute->getType());
        $wp_attribute->setGroup($attribute->getGroup());
        $wp_attribute->setLocalizable($attribute->isLocalizable());
        $wp_attribute->setLabels($attribute->getLabels());
        $wp_attribute->setGroupLabels($attribute->getGroupLabels());
        $wp_attribute->setMetaDatas($attribute->getMetaDatas());
        
        // Générer et définir le hash de l'attribut
        $wp_attribute->setHash($this->generateAttributeHash($attribute));

        return $wp_attribute;
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
    public function getWordpressAttribute(Attribute $akeneoAttribute, $locale = 'en_US'): WP_Attribute
    {
        return $this->fromAttribute($akeneoAttribute, $locale);
    }
}
