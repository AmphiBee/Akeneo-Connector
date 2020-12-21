<?php
namespace AmphiBee\AkeneoConnector\Entities;

use WC_Product_Grouped;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_External;

class ProductType
{
    /**
     * Return the correct object according to product type
     *
     * @access protected
     * @param string $type | Type of product
     * @param int $product_id | ID of the product
     * @return WC_Product_External|\WC_Product|WC_Product_Grouped|WC_Product_Simple|WC_Product_Variable
     */
    public static function getProductObjectInstance(string $type, int $product_id = 0) : object
    {
        // Get an instance of the WC_Product object (depending on his type)
        if ('variable' === $type) {
            $product = new WC_Product_Variable($product_id);
        } elseif ('grouped' === $type) {
            $product = new WC_Product_Grouped($product_id);
        } elseif ('external' === $type) {
            $product = new WC_Product_External($product_id);
        } else {
            $product = new WC_Product_Simple($product_id); // "simple" By default
        }

        return $product;
    }
}
