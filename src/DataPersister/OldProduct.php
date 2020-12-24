<?php

namespace AmphiBee\AkeneoConnector\DataPersister;

/**
 * @TODO delete this class, use ProductDataPersister instead
 */
class OldProduct
{

    /**
     * Set default product settings
     *
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    protected static function setBaseSettings(object &$product, array $args)
    {
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
    }

    /**
     * Set product pricing settings
     *
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    protected static function setPricingSetting(object &$product, array $args)
    {
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
            $product->set_tax_class(isset($args['tax_class']) ? $args['tax_class'] : '');
        }

    }

    /**
     * Set merchandising product settings
     *
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    protected static function setMerchSettings(object &$product, array $args)
    {
        // Featured (boolean)
        $product->set_featured(isset($args['featured']) ? $args['featured'] : false);

        // Upsell and Cross sell (IDs)
        $product->set_upsell_ids(isset($args['upsells']) ? $args['upsells'] : '');
        $product->set_cross_sell_ids(isset($args['cross_sells']) ? $args['upsells'] : '');

        // Reviews, purchase note
        $product->set_reviews_allowed(isset($args['reviews']) ? $args['reviews'] : false);
        $product->set_purchase_note(isset($args['note']) ? $args['note'] : '');

        // Sold Individually
        $product->set_sold_individually(isset($args['sold_individually']) ? $args['sold_individually'] : false);
    }

    /**
     * Set product shipping settings
     *
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    protected static function setShippingSettings(object &$product, array $args)
    {
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
    }

    /**
     * Add product to WooCommerce, if product exist (SKU already used), return product id
     *
     * @access public
     * @param array | Product details
     * @return int | ID of the product
     */
    public static function addProduct(array $args): int
    {
        $product_id = self::getProductIdFromSku($args['sku']);

        // Get an empty instance of the product object (defining it's type)
        $product = ProductType::getProductObjectInstance($args['type'], $product_id);

        if (!$product) return 0;

        self::setBaseSettings($product, $args);
        self::setPricingSetting($product, $args);
        self::setMerchSettings($product, $args);
        self::setShippingSettings($product, $args);

        DownloadableProduct::setDownloadableSettings($product, $args);
        Attachment::registerProductAttachment($product, $args);
        ProductAttribute::registerProductAttributes($product, $args);

        // Save product
        $product_id = $product->save();

        VariableProduct::registerVariations($product_id, $args['variations']);

        return $product_id;
    }

    /**
     * Get product ID from SKU
     *
     * Check in the DB if a product has already a given SKU code
     *
     * @access public
     * @param string $sku SKU code
     * @return mixed    $product_id     Return product ID if exist, false if not
     */
    public static function getProductIdFromSku(string $sku) : int
    {
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));
        return is_null($product_id) ? 0 : (int) $product_id;
    }
}
