<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Adapter\ProductAdapter;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\DataProvider\AttributeDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\CategoryDataProvider;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\i18n;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Option;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product;
use AmphiBee\AkeneoConnector\Helpers\AttributeFormatter;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ProductDataPersister extends AbstractDataPersister
{

    /**
     * @param Product $product
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateProduct(Product $product): void
    {
        try {
            $productAsArray = $this->getSerializer()->normalize($product);

            $languages = i18n::getLanguages();

            if (!$product->isEnabled()) {
                return;
            }

            $baseProduct = [
                'type' => 'simple',
                'name' => '',
                'description' => '',
                'short_description' => '',
                'images' =>  [],
                'sku' => '',
                'regular_price' => '',
                'sale_price' => '',
                'reviews_allowed' => '',
                'attributes' => [],
                'metas' => [],
                'product_cat' => [],
                'external_media' => [],
                'upsells' => [],
                'cross_sells' => [],
            ];

            foreach ($productAsArray as $attrName=>$value) {
                $finalProduct = $baseProduct;

                if ($attrName == 'values') {
                    $this->formatAttributes($finalProduct, $value);
                }
            }

            $associations = $product->getAssociation();

            if (count($associations) > 0) {
                // upsell
                if (isset($associations['UPSELL'])) {
                    $finalProduct['upsells'] = $associations['UPSELL']['products'];
                }

                // cross sell
                if (isset($associations['CROSSSELL'])) {
                    $finalProduct['cross_sells'] = $associations['CROSSSELL']['products'];
                }
            }

            $finalProduct['product_id'] = $this->findProductByAkeneoCode($product->getCode());
            $finalProduct['metas']['_akeneo_code'] = $product->getCode();

            if ($finalProduct['sku'] === '') {
                $finalProduct['sku'] = $product->getCode();
            }

            if ($cats = $product->getCategories()) {
                foreach ($cats as $cat) {
                    $idCat = CategoryDataProvider::findCategoryByAkeneoCode($cat);
                    if ($idCat > 0) {
                        $finalProduct['product_cat'][] = $idCat;
                    }
                }
            }


            var_dump($finalProduct['sku']);
            $this->makeProduct($finalProduct);


        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Product (ModelCode %s) %s',
                print_r($product, true),
                $e->getMessage()
            ));

            return;
        }
    }

    public function findProductByAkeneoCode($akeneoCode) : int
    {
        $args = [
            'fields'        => 'ids',
            'post_type'      => 'product',
            'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'],
            'meta_query'    => [
                'relation'  => 'OR',
                [
                    'key'   => '_akeneo_code',
                    'value' => $akeneoCode,
                ],
                [
                    'key'   => '_sku',
                    'value' => $akeneoCode,
                ]
            ]
        ];

        $query = new \WP_Query( $args );

        return count($query->posts) > 0 ? $query->posts[0] : 0;
    }

    public function makeProduct($args) {

        $type = $args['type'];
        $product_id = $args['product_id'];

        // Get an instance of the WC_Product object (depending on his type)
        if ('variable' === $type) {
            $product = new \WC_Product_Variable($product_id);
        } elseif ('grouped' === $type) {
            $product = new \WC_Product_Grouped($product_id);
        } elseif ('external' === $type) {
            $product = new \WC_Product_External($product_id);
        } else {
            $product = new \WC_Product_Simple($product_id); // "simple" By default
        }

        // Product name (Title) and slug
        $product->set_name($args['name']); // Name (title).
        if (isset($args['slug'])) $product->set_name($args['slug']);

        // Description and short description:
        $product->set_description($args['description']);
        $product->set_short_description($args['short_description']);

        // Status ('publish', 'pending', 'draft' or 'trash')
        $product->set_status(isset($args['status']) ? $args['status'] : 'publish');

        // Visibility ('hidden', 'visible', 'search' or 'catalog')
        $product->set_catalog_visibility(isset($args['visibility']) ? $args['visibility'] : 'visible');

        // Virtual (boolean)
        $product->set_virtual(isset($args['virtual']) ? $args['virtual'] : false);

        // Menu order
        if (isset($args['menu_order'])) $product->set_menu_order($args['menu_order']);

        // Product categories and Tags
        if (isset($args['category_ids'])) $product->set_category_ids('category_ids');
        if (isset($args['tag_ids'])) $product->set_tag_ids($args['tag_ids']);

        // Prices
        $product->set_regular_price($args['regular_price']);
        $product->set_sale_price(isset($args['sale_price']) ? $args['sale_price'] : '');
        $product->set_price(isset($args['sale_price']) ? $args['sale_price'] : $args['regular_price']);

        if (isset($args['sale_price'])) {
            $product->set_date_on_sale_from(isset($args['sale_from']) ? $args['sale_from'] : '');
            $product->set_date_on_sale_to(isset($args['sale_to']) ? $args['sale_to'] : '');
        }

        // Taxes
        if (\get_option('woocommerce_calc_taxes') === 'yes') {
            $product->set_tax_status(isset($args['tax_status']) ? $args['tax_status'] : 'taxable');
            $tax_class = $args['tax_class'][0];
            if ($tax_class === 'tva_55') {
                $tax_class = sanitize_title('Taux réduit');
            }

            $product->set_tax_class(isset($args['tax_class']) ? $tax_class : '');
        }

        // Featured (boolean)
        $product->set_featured(isset($args['featured']) ? $args['featured'] : false);

        // Reviews, purchase note
        $product->set_reviews_allowed(isset($args['reviews']) ? $args['reviews'] : false);
        $product->set_purchase_note(isset($args['note']) ? $args['note'] : '');


        // Sold Individually
        $product->set_sold_individually(isset($args['sold_individually']) ? $args['sold_individually'] : false);

        // SKU and Stock (Not a virtual product)
        $virtual = isset($args['virtual']) && !$args['virtual'];
        $manage_stock = isset($args['manage_stock']) ? $args['manage_stock'] : false;
        $backorders = isset($args['backorders']) ? $args['backorders'] : 'no';
        $stock_status = isset($args['stock_status']) ? $args['stock_status'] : 'instock';

        if (!$virtual) {
            $product->set_sku(isset($args['sku']) ? $args['sku'] : '');
            $product->set_manage_stock($manage_stock);
            $product->set_stock_status($stock_status);
        }

        if (!$virtual && $manage_stock) {
            $product->set_stock_status($args['stock_qty']);
            $product->set_backorders($backorders); // 'yes', 'no' or 'notify'
        }

        // Weight, dimensions and shipping class
        $product->set_weight(isset($args['weight']) ? $args['weight'] : '');
        $product->set_length(isset($args['length']) ? $args['length'] : '');
        $product->set_width(isset($args['width']) ? $args['width'] : '');
        $product->set_height(isset($args['height']) ? $args['height'] : '');

        if (isset($args['shipping_class_id'])) $product->set_shipping_class_id($args['shipping_class_id']);

        // Downloadable (boolean)
        $product->set_downloadable(isset($args['downloadable']) ? $args['downloadable'] : false);

        if (count($args['product_cat']) > 0) {
            $product->set_category_ids($args['product_cat']);
        }

        if (isset($args['downloadable']) && $args['downloadable']) {
            $product->set_downloads(isset($args['downloads']) ? $args['downloads'] : []);
            $product->set_download_limit(isset($args['download_limit']) ? $args['download_limit'] : '-1');
            $product->set_download_expiry(isset($args['download_expiry']) ? $args['download_expiry'] : '-1');
        }

        // Images and Gallery
        /*
        if (isset($args['images']) && is_array($args['images'])) {
            $image_id = self::assignRemoteAttachment($args['images'][0]);
            $product->set_image_id($image_id ? $image_id : "");
        }

        $gallery_ids = [];

        if (count($args['images']) > 1) {
            array_shift($args['images']);
            foreach ($args['images'] as $img) {
                $gallery_ids[] = self::assignRemoteAttachment($img);
            }
        }

        $product->set_gallery_image_ids($gallery_ids);
        */

        // Attributes and default attributes
        if (isset($args['attributes'])) {
            $product->set_attributes($this->prepareProductAttributes($args['attributes']));
        }

        if (isset($args['default_attributes'])) {
            $product->set_default_attributes($args['default_attributes']);
        }

        $product_id = $product->save();

        foreach ($args['metas'] as $meta_key=>$meta_value) {
            $meta_key = apply_filters('ak/product/meta_key', $meta_key, $meta_value, $product_id);
            $meta_value = apply_filters('ak/product/meta_value', $meta_value, $product_id, $meta_key);
            update_post_meta($product_id, $meta_key, $meta_value);
        }

        // Upsell and Cross sell (IDs) after all product saved

        add_action('ak/product/after_import', function($products) use ($product, $args) {

            $upsells = [];
            $cross_sells = [];
            foreach (['upsells', 'cross_sells'] as $association) {
                if (count($args[$association]) == 0) {
                    continue;
                }

                foreach ($args[$association] as $associationSku) {
                    $associationId = Product::getProductIdFromSku($associationSku);
                    if ($associationId > 0) {
                        array_push($$association, $associationId);
                    }
                }
            }

            $product->set_upsell_ids($upsells);
            $product->set_cross_sell_ids($cross_sells);
            $product->save();
        });

        do_action('ak/product/external_gallery', $product_id, $args['external_gallery']);
        do_action('ak/product/external_media', $product_id, $args['external_media']);

        do_action('ak/product/after_save', $product_id, $args);
    }

    /**
     * Create product attributes if needed
     * @access public
     * @param $attributes | Product attributes
     * @return array | Final attributes array
     */
    public function prepareProductAttributes(array $attributes): array
    {
        $data = [];
        $position = 0;

        foreach ($attributes as $taxonomy => $values) {

            if ($values['is_taxonomy']) {
                $taxonomy = strtolower('pa_' . $taxonomy);
                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }

                // Get an instance of the WC_Product_Attribute Object
                $attribute = new \WC_Product_Attribute();

                // Loop through the term names

                foreach ($values['term_ids'] as $key=>$term_id) {
                    // Get and set the term ID in the array from the term name
                    if (!\term_exists($term_id, $taxonomy)) {
                        unset($values['term_ids'][$key]);
                    }
                }


                $taxonomy_id = \wc_attribute_taxonomy_id_by_name($taxonomy); // Get taxonomy ID

                $attribute->set_id($taxonomy_id);
                $attribute->set_name($taxonomy);
                $attribute->set_options($values['term_ids']);
                $attribute->set_position($position);
                $attribute->set_visible($values['is_visible']);
                $attribute->set_variation($values['is_variation']);

                $data[$taxonomy] = $attribute; // Set in an array
            } else {

                // Get an instance of the WC_Product_Attribute Object
                $attribute = new \WC_Product_Attribute();
                $attribute->set_id(0);
                $attribute->set_name($taxonomy);
                $attribute->set_options($values['value']);
                $attribute->set_position($position);
                $attribute->set_visible($values['is_visible']);
                $attribute->set_variation($values['is_variation']);

                $data[$taxonomy] = $attribute; // Set in an array
            }

            $position++; // Increase position
        }

        return $data;
    }

    public function formatAttributes(array &$product, array $attrs) {
        foreach ($attrs as $attrKey=>$attrValue) {
            $attrType = AttributeDataProvider::getAttributeType($attrKey);
            $attrValue = AttributeFormatter::process($attrValue, $attrType);
            $mapping = Settings::getMappingValue($attrKey);

            if (($mapping === 'global_attribute' || $mapping === 'text_attribute') && ( $attrValue || $attrType === 'pim_catalog_boolean')) {
                if ($mapping === 'global_attribute') {

                    $taxonomy = 'pa_' . strtolower($attrKey);
                    if ($attrType === 'pim_catalog_boolean') {
                        $boolLabel = $attrValue === true ? 'Oui' : 'Non';
                        $term = get_term_by('name', $boolLabel, $taxonomy);
                        $attrValue = (array) $term->term_id;
                    } else {
                        $assocValues = [];
                        // Flatten array, needed for WooCommerce
                        if (isset($attrValue[0][0])) {
                            $attrValue = AttributeFormatter::arrayFlatten($attrValue);
                        }

                        foreach ($attrValue as $val) {
                            $option = (new Option($val))->findOptionByAkeneoCode($taxonomy);

                            if ($option > 0) {
                                $assocValues[] = $option;
                            }
                        }
                        $attrValue = $assocValues;
                    }

                } else {
                    $attrKey = AttributeDataProvider::getAttributeLabel($attrKey);
                }

                $product['attributes'][$attrKey] = [
                    'is_taxonomy' => ($mapping === 'global_attribute'),
                    'is_visible' => true,
                    'is_variation' => false,
                ];

                if ($mapping === 'global_attribute') {
                    $product['attributes'][$attrKey]['term_ids'] = $attrValue;
                } else {
                    $product['attributes'][$attrKey]['value'] = (array)$attrValue;
                }

            } elseif ($mapping === 'post_title') {
                $product['name'] = $attrValue;
            } elseif ($mapping === 'post_excerpt') {
                $product['short_description'] = $attrValue;
            } elseif ($mapping === 'post_content') {
                $product['description'] = $attrValue;
            } elseif ($mapping === 'post_thumbnail') {
                $product['thumbnail'] = $attrValue;
            } elseif ($mapping === 'ugs') {
                $product['sku'] = $attrValue;
            } elseif ($mapping === 'gallery') {
                $product['images'] = $attrValue;
            } elseif ($mapping === 'post_title') {
                $product['name'] = $attrValue;
            } elseif ($mapping === 'post_meta') {
                $product['metas'][$attrKey] = $attrValue;
            } elseif ($mapping === 'external_media') {
                $attrKey = AttributeDataProvider::getAttributeLabel($attrKey);
                $product['external_media'][$attrKey] = $attrValue;
            } else {
                $product[$mapping] = $attrValue;
            }
        }

    }
}
