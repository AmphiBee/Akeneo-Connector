<?php
namespace AmphiBee\AkeneoConnector\Entities;

use WC_Product_Variation;

class VariableProduct
{
    /**
     * Register variations related to a parent product
     *
     * @param int $product_id | ID of parent product
     * @param array $variations | List of variable products
     */
    public static function registerVariations(int $product_id, array $variations) {
        foreach ($variations as $variation) {
            self::createVariation($product_id, $variation);
        }
    }

    /**
     * Create a product variation for a defined variable product ID.
     *
     * @access public
     * @param int $product_id | Post ID of the product parent variable product.
     * @param array $variation_data | The data to insert in the product.
     * @since 3.0.0
     */
    public static function createVariation(int $product_id, array $variation_data) : int
    {
        // Get the Variable product object (parent)
        $variation_id = Product::getProductIdFromSku($variation_data['sku']);

        if (!$variation_id) {
            $product = \wc_get_product($product_id);

            $variation_post = [
                'post_title' => $product->get_name(),
                'post_name' => 'product-' . $product_id . '-variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type' => 'product_variation',
                'guid' => $product->get_permalink()
            ];

            // Creating the product variation
            $variation_id = \wp_insert_post($variation_post);

            if (!$variation_id) {
                return 0;
            }
        }

        // Get an instance of the WC_Product_Variation object
        $variation = new WC_Product_Variation($variation_id);

        // Iterating through the variations attributes
        foreach ($variation_data['attributes'] as $attribute => $term_name) {
            $taxonomy = 'pa_' . $attribute; // The attribute taxonomy

            // If taxonomy doesn't exists we create it
            if (!\taxonomy_exists($taxonomy)) {
                \register_taxonomy(
                    $taxonomy,
                    'product_variation',
                    [
                        'hierarchical' => false,
                        'label' => ucfirst($attribute),
                        'query_var' => true,
                        'rewrite' => ['slug' => \sanitize_title($attribute)], // The base slug
                    ],
                );
            }

            // Check if the Term name exist and if not we create it.
            if (!term_exists($term_name, $taxonomy)) {
                \wp_insert_term($term_name, $taxonomy);
            } // Create the term

            $term_slug = \get_term_by('name', $term_name, $taxonomy)->slug; // Get the term slug

            // Get the post Terms names from the parent variable product.
            $post_term_names = \wp_get_post_terms($product_id, $taxonomy, ['fields' => 'names']);

            // Check if the post term exist and if not we set it in the parent variable product.
            if (!in_array($term_name, $post_term_names)) {
                \wp_set_post_terms($product_id, $term_name, $taxonomy, true);
            }

            // Set/save the attribute data in the product variation
            \update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
        }

        ## Set/save all other data

        // SKU
        if (!empty($variation_data['sku'])) {
            $variation->set_sku($variation_data['sku']);
        }

        // Prices
        if (empty($variation_data['sale_price'])) {
            $variation->set_price($variation_data['regular_price']);
        } else {
            $variation->set_price($variation_data['sale_price']);
            $variation->set_sale_price($variation_data['sale_price']);
        }
        $variation->set_regular_price($variation_data['regular_price']);

        // Stock
        if (!empty($variation_data['stock_qty'])) {
            $variation->set_stock_quantity($variation_data['stock_qty']);
            $variation->set_manage_stock(true);
            $variation->set_stock_status('');
        } else {
            $variation->set_manage_stock(false);
        }

        $variation->set_weight(''); // weight (reseting)

        // Images
        if (isset($variation_data['image'])) {
            $image_id = Attachment::assignRemoteAttachment($variation_data['image']);
            $variation->set_image_id($image_id ? $image_id : "");
        }

        return $variation->save(); // Save the data
    }
}
