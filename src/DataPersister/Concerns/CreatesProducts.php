<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister\Concerns;

use AmphiBee\AkeneoConnector\DataProvider\FamilyVariantDataProvider;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product;
use WC_Product_Simple;
use OP\Lib\WpEloquent\Model\Post;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;
use AmphiBee\AkeneoConnector\Models\ProductModel;
use AmphiBee\AkeneoConnector\Helpers\AttributeFormatter;
use AmphiBee\AkeneoConnector\DataProvider\AttributeDataProvider;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Model as WP_Model;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product as WP_Product;
use Carbon\Carbon;

trait CreatesProducts
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
    private $familyVariantDataProvider;

    /**
     * @param FamilyVariantDataProvider $familyVariantDataProvider
     */
    public function __construct(FamilyVariantDataProvider $familyVariantDataProvider)
    {
        $this->familyVariantDataProvider = $familyVariantDataProvider;
        parent::__construct();
    }

    public function getFamilyVariantDataProvider(): FamilyVariantDataProvider
    {
        return $this->familyVariantDataProvider;
    }


    /**
     * Save a single product by locale.
     *
     * @param array                $product_parsed  The default product attributes
     * @param WP_Product|WP_Model  $product
     * @param string               $locale
     *
     * @return int
     */
    protected function updateSingleElement(array $product_parsed, $product, string $locale)
    {
        $this->formatAttributes($product_parsed, $product->getValues(), $locale);
        $this->formatAssociations($product_parsed, $product->getAssociation());

        if (!$product_parsed['name']) {
            return 0; # A girl has no name.
        }

        $product_parsed['product_id']            = Fetcher::getProductIdBySku($product->getCode(), $locale);
        $product_parsed['metas']['_akeneo_lang'] = $locale;
        $product_parsed['metas']['_akeneo_code'] = $product->getCode();

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
     * @param array  &$product  The product as array containing WooCommerce data
     * @param array  $attrs     The Attributes
     * @param string $locale    The current locale
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

            if (in_array($mapping, ['global_attribute', 'text_attribute', 'private_global_attribute', 'private_text_attribute'])) {
                if ($mapping === 'global_attribute' || $mapping === 'private_global_attribute') {
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
                    'is_taxonomy'  => ($mapping === 'global_attribute' || $mapping === 'private_global_attribute'),
                    'is_visible'   => ($mapping === 'global_attribute' || $mapping === 'text_attributes'),
                    'is_variation' => false,
                ];

                if ($mapping === 'global_attribute' || $mapping === 'private_global_attribute') {
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
     * Format the a single Akeneo variable attribute for Wordpress, reading the mapping values.
     *
     * @param string $attr_key The variable attribute key/code
     *
     * @return array
     */
    public function formatVariableAttribute(string $attr_key): array
    {
        $mapping = Settings::getMappingValue($attr_key);

        return [
            'is_taxonomy'  => ($mapping === 'global_attribute' || $mapping === 'private_global_attribute'),
            'is_visible'   => true,
            'is_variation' => true,
            'term_ids'     => [],
        ];
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
    public function makeProduct(array $args, string $locale): ?int
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
            $sale_from = Carbon::parse($args['sale_from']);
            $sale_to = Carbon::parse($args['sale_to']);
            $product->set_date_on_sale_from($sale_from->isValid() ? $sale_from->startOfDay()->toIso8601String() : '');
            $product->set_date_on_sale_to($sale_to->isValid() ? $sale_to->endOfDay()->toIso8601String() : '');
        }

        // Taxes
        if (\get_option('woocommerce_calc_taxes') === 'yes') {
            $product->set_tax_status(isset($args['tax_status']) ? $args['tax_status'] : 'taxable');
            $tax_class = '';
            if (!empty($args['tax_class'])) {
                $tax_class = is_array($args['tax_class']) ? $args['tax_class'][0] : $args['tax_class'];
            }

            if ($tax_class === 'tva_55') {
                $tax_class = \sanitize_title('Taux réduit');
            }

            $product->set_tax_class(isset($args['tax_class']) ? $tax_class : '');
        }

        // Featured (boolean)
        $product->set_featured(isset($args['featured']) ? $args['featured'] : false);

        // Reviews, purchase note
        $args['reviews_allowed'] = apply_filters('ak/f/product/single/reviews_allowed', $args['reviews_allowed'] ?? get_option('woocommerce_enable_reviews', 'yes') === 'yes');

        if (isset($args['reviews_allowed'])) {
            $product->set_reviews_allowed($args['reviews_allowed']);
        }

        if (isset($args['purchase_note'])) {
            $product->set_purchase_note($args['note']);
        }
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
     * Build a variation for the given product, based on the model and the variation WP_Product.
     *
     * @param  int           $model      The WC variable product id.
     * @param  WP_Product    $product    The variant product comming from Akeneo API.
     * @param  array         $atributes  The variation variable attributes, as Akeneo codes.
     * @param  string        $locale     The current locale to save the variation in.
     *
     * @return void
     */
    public function makeVariation(int $product_id, WP_Product $product, array $attributes, string $locale)
    {
        $data       = static::$base_product;
        $wc_product = wc_get_product($product_id);
        $variation  = Fetcher::getProductVariationByAkeneoCode($product->getCode(), $locale);

        if ($variation) {
            $variation_id = $variation->id;
        } else {
            $name = '';
            $guid = '';
            if ($wc_product instanceof \WC_Product) {
                $name = $wc_product->get_name();
                $guid = $wc_product->get_permalink();
            } elseif ($product instanceof Product) {
                $values = $product->getValues();
                $name = collect($values['name'])->first()['data'];
                $guid = getenv('WP_HOME') . '/' . sanitize_title($name);
            }

            $variation_id = \wp_insert_post([
                'post_title'  => $name,
                'post_name'   => 'product-'.$product_id.'-variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type'   => 'product_variation',
                'guid'        => $guid,
                'meta_input'  => [
                    '_akeneo_code' => $product->getCode(),
                    '_akeneo_lang' => $locale,
                ],
            ]);
        }

        # Get an instance of the WC_Product_Variation object
        $variation = new \WC_Product_Variation($variation_id);

        $values = $product->getValues();

        $this->formatAttributes($data, $values, $locale);

        foreach ($attributes as $attribute) {
            $val      = $values[$attribute][0]['data'] ?? '';
            $taxonomy = strtolower('pa_' . $attribute);
            $term_id  = Fetcher::getTermIdByAkeneoCode($val, $taxonomy, $locale);

            if (!$term_id) {
                return; # Missing attribute term, skip this variation.
            }

            $term = get_term($term_id);

            $post_attr_terms = wp_get_post_terms($product_id, $taxonomy);

            # Make sure the term is present in the variable product variant attribute term list
            if (!in_array($term, $post_attr_terms)) {
                wp_set_post_terms($product_id, [$term->term_id], $taxonomy, true);
            }

            # Save variation meta
            update_post_meta($variation_id, 'attribute_'.$taxonomy, $term->slug);
        }

        # Sku
        $variation->set_sku($product->getCode());

        # Prices
        $regular = isset($data['regular_price'][0]) ? collect($data['regular_price'][0])->get('amount', '') : ($data['regular_price'] ?? '');
        $sale    = isset($data['sale_price'][0]) ? collect($data['sale_price'][0])->get('amount', '') : ($data['sale_price'] ?? '');

        $variation->set_regular_price($regular);
        $variation->set_sale_price($sale ?: '');
        $variation->set_price($sale ?: $regular);

        if (isset($data['sale_price'])) {
            $sale_from = Carbon::parse($data['sale_from']);
            $sale_to = Carbon::parse($data['sale_to']);
            $variation->set_date_on_sale_from($sale_from->isValid() ? $sale_from->startOfDay()->toIso8601String() : '');
            $variation->set_date_on_sale_to($sale_to->isValid() ? $sale_to->endOfDay()->toIso8601String() : '');
        }

        # Stock (Not a virtual product)
        $virtual      = isset($data['virtual']) && !$data['virtual'];
        $manage_stock = isset($data['manage_stock']) ? $data['manage_stock'] : false;
        $backorders   = isset($data['backorders']) ? $data['backorders'] : 'no';
        $stock_status = isset($data['stock_status']) ? $data['stock_status'] : 'instock';

        if (!$virtual) {
            $variation->set_manage_stock($manage_stock);
            $variation->set_stock_status($stock_status);
        }

        if (!$virtual && $manage_stock) {
            $variation->set_stock_status($data['stock_qty']);
            $variation->set_backorders($backorders); // 'yes', 'no' or 'notify'
        }

        # Save the variation
        $variation->save();

        # Publish the product which was in draft waiting for a variation
        $wc_product->set_status('publish');
        $wc_product->save();

        do_action('ak/a/product/variable/external_gallery', $variation_id, $data['external_gallery'] ?? [], $locale);
        do_action('ak/a/product/variable/external_media', $variation_id, $data['external_media'] ?? [], $locale);

        do_action('ak/a/product/variable/after_save', $variation, $wc_product, $data, $attributes, $locale);
    }


    /**
     * Create product attributes if needed.
     *
     * @param   array  $attributes  Product attributes
     * @return  array  Final attributes array
     *
     * @since 1.0
     * @version 1.13.0
     * @access public
     */
    public function prepareProductAttributes(array $attributes): array
    {
        $data = [];
        $position = 0;

        foreach ($attributes as $taxonomy => $values) {
            # Get an instance of the WC_Product_Attribute Object
            $attribute = new \WC_Product_Attribute();
            $taxonomy_id = 0;
            $options = [];

            if ($values['is_taxonomy']) {
                $taxonomy = strtolower('pa_' . $taxonomy);

                if (!taxonomy_exists($taxonomy) || !isset($values['term_ids']) || !is_array($values['term_ids'])) {
                    continue;
                }

                $taxonomy_id = \wc_attribute_taxonomy_id_by_name($taxonomy); // Get taxonomy ID
                $options = $values['term_ids'];
            } else if (!empty($values['value'])) {
                $options = $values['value'];
            }

            $attribute->set_id($taxonomy_id);
            $attribute->set_name($taxonomy);
            $attribute->set_options($options);
            $attribute->set_position($position);
            $attribute->set_visible($values['is_visible']);
            $attribute->set_variation($values['is_variation']);

            $data[$taxonomy] = $attribute;

            $position++; // Increase position
        }

        return $data;
    }

    public function getCodeFromModel($model)
    {
        $ak_variant_attribute = $this->getFamilyVariantDataProvider()->get($model->family_code, $model->variant_code);
        $code = $model->variant_code;
        foreach ($ak_variant_attribute['variant_attribute_sets'] as $attribute_set) {
            $level = $attribute_set['level'];
            if ($level === 1 && !empty($attribute_set['axes'][0])) {
                $code = $attribute_set['axes'][0];
            }
        }

        return $code;
    }
}
