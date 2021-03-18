<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Adapter\AttributeAdapter;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AttributeDataPersister extends AbstractDataPersister
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'attributeRegister']);
    }

    public function attributeRegister() {
        $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();
        $attributeAdapter = new AttributeAdapter();

        /** @var \AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute $AknAttr */
        foreach ($attributeDataProvider->getAll() as $AknAttr) {
            $wooCommerceAttribute = $attributeAdapter->getWordpressAttribute($AknAttr);
            $this->createOrUpdateAttribute($wooCommerceAttribute);
        }
    }

    /**
     * @param Attribute $attribute
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @todo remove suppress warning
     */
    public function createOrUpdateAttribute(Attribute $attribute): void
    {
        try {
            $attrCode = $attribute->getCode();
            $attrName = $attribute->getName();
            $mapping = Settings::getMappingValue($attrCode);

            if ($mapping === 'global_attribute') {

                $attributes = \wc_get_attribute_taxonomies();
                $slugs = \wp_list_pluck( $attributes, 'attribute_name' );

                if ( ! in_array( $attrCode, $slugs ) ) {
                    $args = array(
                        'slug'    => $attrCode,
                        'name'   => $attrName,
                        'type'    => 'select',
                        'orderby' => 'menu_order',
                        'has_archives'  => false,
                    );
                    \wc_create_attribute( $args );

                    if ($attribute->getType() === 'pim_catalog_boolean') {
                        $choices = ['Oui', 'Non'];

                        foreach ($choices as $choice) {
                            $taxonomy = strtolower("pa_{$attrCode}");
                            if (taxonomy_exists($taxonomy) && !term_exists($choice, $taxonomy)) {
                                wp_insert_term($choice, $taxonomy);
                            }
                        }
                    }
                }
            }
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Attribute (Attr Code %s) %s',
                print_r($attribute, true),
                $e->getMessage()
            ));
            return;
        }
    }
}
