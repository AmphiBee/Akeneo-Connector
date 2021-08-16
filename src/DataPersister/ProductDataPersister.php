<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use Monolog\Logger;
use OP\Lib\WpEloquent\Model\Post;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Helpers\AttributeFormatter;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\DataProvider\AttributeDataProvider;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product as WP_Product;

class ProductDataPersister extends AbstractDataPersister
{
    /**
     * The default product structure.
     *
     * @var array
     */
    protected static $base_product = [
        'type'              => 'simple',
        'name'              => '',
        'description'       => '',
        'short_description' => '',
        'images'            => [],
        'sku'               => '',
        'regular_price'     => '',
        'sale_price'        => '',
        'reviews_allowed'   => '',
        'attributes'        => [],
        'metas'             => [],
        'product_cat'       => [],
        'external_media'    => [],
        'external_gallery'  => [],
        'upsells'           => [],
        'cross_sells'       => [],
    ];

    /**
     * The product attributes mapping aliases.
     *
     * @var array
     */
    protected static $aliases = [
        'post_title'     => 'name',
        'post_excerpt'   => 'short_description',
        'post_content'   => 'description',
        'post_thumbnail' => 'thumbnail',
        'ugs'            => 'sku',
        'gallery'        => 'images',
    ];

    /**
     * The attributes mapping that should be decoded using json_decode().
     *
     * @var array
     */
    protected static $cast_json = [
        'external_media',
        'external_gallery',
    ];


    /**
     * @param Product $product
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdate(WP_Product $product): void
    {
        try {
            if (!$product->isEnabled()) {
                return;
            }

            $ids = [];

            $available_locales = $this->translator->available;

            foreach ($available_locales as $locale) {
                $slug = $this->translator->localeToSlug($locale);

                # Set current lang context, to avoid unwanted translations by Polylang/WPML
                $this->translator->setCurrentLang($slug);

                # Save ids in array to sync them as translation of each others
                $ids[$slug] = $this->updateSingleProduct($product, $locale);
            }

            $ids = array_filter($ids);

            # Set terms as translation of each others
            if (count($ids) > 1) {
                $this->translator->syncPosts($ids);
            }

            # catch error
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Product (ModelCode %s) %s',
                print_r($product, true),
                $e->getMessage()
            ));

            return;
        }
    }


    /**
     * Save a single product by locale.
     *
     * @param WP_Category  $category
     * @param string       $locale
     *
     * @return int
     */
    public function updateSingleProduct(WP_Product $product, string $locale)
    {
        $product_parsed = static::$base_product;

        $this->formatAttributes($product_parsed, $product->getValues(), $locale);
        $this->formatAssociations($product_parsed, $product->getAssociation());

        # A girl has no name.
        if (!$product_parsed['name']) {
            return 0;
        }

        $product_parsed['product_id']            = Fetcher::getProductIdBySku($product->getCode(), $locale);
        $product_parsed['metas']['_akeneo_lang'] = $locale;

        if ($product_parsed['sku'] === '') {
            $product_parsed['sku'] = $product->getCode();
        }

        if ($cats = $product->getCategories()) {
            foreach ($cats as $cat) {
                $idCat = Fetcher::getTermIdByAkeneoCode($cat, 'product_cat', $locale);

                if ($idCat > 0) {
                    $product_parsed['product_cat'][] = $idCat;
                }
            }
        }

        $product_id = $this->makeProduct($product_parsed, $locale);

        if ($product_id) {
            $this->translator->setPostLang($product_id, $locale);
        }

        return $product_id;
    }


    /**
     * Format the Akeneo attributes for Wordpress, reading the mapping values.
     *
     * @param array &$product  The product as array containing WooCommerce data
     * @param array $attrs     The Attributes
     * @param array $locale    The current locale
     *
     * @return void
     */
    public function formatAttributes(array &$product, array $attrs, string $locale)
    {
        $aliases  = static::$aliases;

        foreach ($attrs as $attr_key => $attr_value) {
            $attr_type  = AttributeDataProvider::getAttributeType($attr_key);
            $attr_value = AttributeFormatter::process($attr_value, $attr_type, $locale);
            $mapping    = Settings::getMappingValue($attr_key);

            if (!$attr_value) {
                continue;
            }

            if (in_array($mapping, ['global_attribute', 'text_attribute'])) {
                if ($mapping === 'global_attribute') {
                    $taxonomy = sprintf('pa_%s', strtolower($attr_key));

                    if ($attr_type === 'pim_catalog_boolean') {
                        $label      = $attr_value === true ? 'true' : 'false';
                        $term       = Fetcher::getTermBooleanByAkeneoCode($label, $taxonomy, $locale);
                        $attr_value = (array) ($term ? $term->term_id : null);
                    } else {
                        # If we got an array
                        if (is_array($attr_value)) {
                            # Flatten array, needed for WooCommerce
                            if (isset($attr_value[0][0])) {
                                $attr_value = AttributeFormatter::arrayFlatten($attr_value);
                            }

                            $terms = [];

                            foreach ($attr_value as $val) {
                                $terms[] = Fetcher::getTermIdByAkeneoCode($val, $taxonomy, $locale);
                            }

                            $attr_value = array_filter($terms);

                        # If we got a string
                        } elseif (is_string($attr_value)) {
                            $option     = Fetcher::getTermIdByAkeneoCode($attr_value, $taxonomy, $locale);
                            $attr_value = $option ? [$option] : [];
                        }
                    }
                } else {
                    $attr_key = AttributeDataProvider::getAttributeLabel($attr_key);
                }

                $product['attributes'][$attr_key] = [
                    'is_taxonomy'  => ($mapping === 'global_attribute'),
                    'is_visible'   => true,
                    'is_variation' => false,
                ];

                if ($mapping === 'global_attribute') {
                    $product['attributes'][$attr_key]['term_ids'] = $attr_value;
                } else {
                    $product['attributes'][$attr_key]['value'] = (array) $attr_value;
                }
            } elseif ($mapping === 'post_meta') {
                $product['metas'][$attr_key] = $attr_value;
            } elseif ($mapping === 'external_media') {
                $attr_key = AttributeDataProvider::getAttributeLabel($attr_key, $locale);
                $product['external_media'][$attr_key] = $attr_value;
            } elseif ($mapping === 'product_tag') {
                $ids    = [];

                foreach ($attr_value as $value) {
                    $ids[] = Fetcher::getTermIdByAkeneoCode($value, 'product_tag', $locale);
                }

                $product['product_tag'] = array_merge(($product['product_tag'] ?? []), array_filter($ids));
            } else {
                if (array_key_exists($mapping, $aliases)) {
                    $mapping = $aliases[$mapping];
                }
                if (in_array($mapping, static::$cast_json) && is_string($attr_value)) {
                    $attr_value = json_decode($attr_value);
                }
                $product[$mapping] = $attr_value;
            }
        }
    }


    /**
     * Format the Akeneo attributes for Wordpress, reading the mapping values.
     *
     * @param array &$product      The product as array containing WooCommerce data
     * @param array $associations  The associations
     *
     * @return void
     */
    public function formatAssociations(array &$product, array $associations)
    {
        if (count($associations) > 0) {
            // upsell
            if (isset($associations['UPSELL'])) {
                $product['upsells'] = $associations['UPSELL']['products'];
            }

            // cross sell
            if (isset($associations['CROSSSELL'])) {
                $product['cross_sells'] = $associations['CROSSSELL']['products'];
            }
        }
    }


    /**
     * Make or update a product from parsed array.
     *
     * @return int The product id
     */
    public function makeProduct($args, $locale): ?int
    {
        $type       = $args['type'];
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
        if (isset($args['slug'])) {
            $product->set_slug($args['slug']);
        }

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
        if (isset($args['menu_order'])) {
            $product->set_menu_order($args['menu_order']);
        }

        // Prices
        $regular = isset($args['regular_price'][0]) ? collect($args['regular_price'][0])->get('amount', '') : ($args['regular_price'] ?? '');
        $sale    = isset($args['sale_price'][0]) ? collect($args['sale_price'][0])->get('amount', '') : ($args['sale_price'] ?? '');
        $product->set_regular_price($regular);
        $product->set_sale_price($sale ?: '');
        $product->set_price($sale ?: $regular);

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
        $virtual      = isset($args['virtual']) && !$args['virtual'];
        $manage_stock = isset($args['manage_stock']) ? $args['manage_stock'] : false;
        $backorders   = isset($args['backorders']) ? $args['backorders'] : 'no';
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
        if (isset($args['weight'])) {
            $weight = $args['weight']['amount'] ?? $args['weight'];
        }
        $product->set_weight($weight ?? '');
        $product->set_length($args['length'] ?? '');
        $product->set_width($args['width'] ?? '');
        $product->set_height($args['height'] ?? '');

        if (isset($args['shipping_class_id'])) {
            $product->set_shipping_class_id($args['shipping_class_id']);
        }

        // Downloadable (boolean)
        $product->set_downloadable(isset($args['downloadable']) ? $args['downloadable'] : false);

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


        /**
         * SAVE
         */
        $product_id = $product->save();
        /**
         * SAVE
         */


        /**
         * Sync the taxonomies. We don't use something like `$product->set_category_ids()`
         * on purpose to avoid getting terms translated by Polylang/WPML.
         */
        $taxonomies = [
            'product_cat' => $args['product_cat'] ?? [],
            'product_tag' => $args['product_tag'] ?? [],
        ];

        $model = Post::with('taxonomies')->findOrFail($product_id);

        # Get the post terms ids grouped by taxonomy
        $model_terms = $model->taxonomies->groupBy('taxonomy')->map(function ($group) {
            return $group->map(function ($item) {
                return $item->term->id;
            });
        })->toArray();


        # Replace the product taxonomy terms by new ones
        foreach ($taxonomies as $taxonomy => $terms) {
            if (!empty($terms)) {
                $model_terms[$taxonomy] = $terms;
            }
        }

        # Sync all taxonomies pivots for this model
        $model->taxonomies()->sync(array_flatten($model_terms));


        foreach ($args['metas'] as $meta_key => $meta_value) {
            $meta_key   = apply_filters('ak/f/product/single/meta_key', $meta_key, $meta_value, $product_id);
            $meta_value = apply_filters('ak/f/product/single/meta_value', $meta_value, $product_id, $meta_key);
            update_post_meta($product_id, $meta_key, $meta_value);
        }

        // Upsell and Cross sell (IDs) after all product saved

        add_action('ak/a/products/after_import', function ($products) use ($product, $args, $locale) {
            $upsells     = [];
            $cross_sells = [];

            foreach (['upsells', 'cross_sells'] as $association) {
                if (count($args[$association]) == 0) {
                    continue;
                }

                foreach ($args[$association] as $associationSku) {
                    $associationId = Fetcher::getProductIdBySku($associationSku, $locale);
                    if ($associationId > 0) {
                        array_push($$association, $associationId);
                    }
                }
            }

            $product->set_upsell_ids($upsells);
            $product->set_cross_sell_ids($cross_sells);
            $product->save();
        });

        // ak/a/product/single/external_gallery

        # Actions
        do_action('ak/a/product/single/external_gallery', $product_id, $args['external_gallery'] ?? [], $locale);
        do_action('ak/a/product/single/external_media', $product_id, $args['external_media'] ?? [], $locale);
        do_action('ak/a/product/single/after_save', $product_id, $args, $locale);

        # Keep this for backwards compatibility
        do_action('ak/product/external_gallery', $product_id, $args['external_gallery'] ?? [], $locale);
        do_action('ak/product/external_media', $product_id, $args['external_media'] ?? [], $locale);
        do_action('ak/product/after_save', $product_id, $args, $locale);


        return $product_id;
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

                if (!taxonomy_exists($taxonomy) || !isset($values['term_ids']) || !is_array($values['term_ids'])) {
                    continue;
                }

                // Get an instance of the WC_Product_Attribute Object
                $attribute = new \WC_Product_Attribute();

                /**
                 * What is this section for ?
                 * We are getting ids from database, they should already exists.
                 */
                // Loop through the term names
                // foreach ($values['term_ids'] as $key => $term_id) {
                //     // Get and set the term ID in the array from the term name
                //     if (!\term_exists($term_id, $taxonomy)) {
                //         unset($values['term_ids'][$key]);
                //     }
                // }


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
}
