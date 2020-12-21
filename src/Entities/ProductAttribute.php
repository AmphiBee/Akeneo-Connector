<?php
namespace AmphiBee\AkeneoConnector\Entities;

use WC_Product_Attribute;

class ProductAttribute
{
    /**
     * Register attributes for a product
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    public static function registerProductAttributes(object &$product, array $args)
    {
        // Attributes and default attributes
        if (isset($args['attributes'])) {
            $product->set_attributes(self::prepareProductAttributes($args['attributes']));
        }
        if (isset($args['default_attributes'])) {
            $product->set_default_attributes($args['default_attributes']);
        }
    }

    /**
     * Create product attributes if needed
     * @access public
     * @param $attributes | Product attributes
     * @return array | Final attributes array
     */
    public static function prepareProductAttributes(array $attributes): array
    {
        $data = [];
        $position = 0;

        foreach ($attributes as $taxonomy => $values) {
            $taxonomy = 'pa_' . $taxonomy;
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            // Get an instance of the WC_Product_Attribute Object
            $attribute = new WC_Product_Attribute();

            $term_ids = [];

            // Loop through the term names
            foreach ($values['term_names'] as $term_name) {
                // Get and set the term ID in the array from the term name
                if (\term_exists($term_name, $taxonomy)) {
                    $term_ids[] = \get_term_by('name', $term_name, $taxonomy)->term_id;
                } else {
                    continue;
                }
            }

            $taxonomy_id = \wc_attribute_taxonomy_id_by_name($taxonomy); // Get taxonomy ID

            $attribute->set_id($taxonomy_id);
            $attribute->set_name($taxonomy);
            $attribute->set_options($term_ids);
            $attribute->set_position($position);
            $attribute->set_visible($values['is_visible']);
            $attribute->set_variation($values['for_variation']);

            $data[$taxonomy] = $attribute; // Set in an array

            $position++; // Increase position
        }

        return $data;
    }
}
