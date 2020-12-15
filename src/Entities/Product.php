<?php

namespace AmphiBee\AkeneoConnector\Entities;

use WC_Product_Attribute;
use WC_Product_Grouped;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class Product
{

	/**
	 * Add Product
	 *
	 * Add product to WooCommerce, if product exist (SKU already used), return product id
	 *
	 * Check $default_args for datas
	 * You can add custom field by adding new row into metas, ie:
	 * $args = array( ..., 'metas'   => array( ... 'my-custom-meta-key' => 'value', ... ) )
	 *
	 * @access public
	 * @param array | Product details
	 * @return int | ID of the product
	 */
	public static function addProduct(array $args): int
	{
		$product_id = self::getProductIdFromSku($args['sku']);

		// Get an empty instance of the product object (defining it's type)
		$product = self::getProductObjectType($args['type'], $product_id);

		if (!$product) {
			return 0;
		}

		// Product name (Title) and slug
		$product->set_name($args['name']); // Name (title).
		if (isset($args['slug'])) {
			$product->set_name($args['slug']);
		}

		// Description and short description:
		$product->set_description($args['description']);
		$product->set_short_description($args['short_description']);

		// Status ('publish', 'pending', 'draft' or 'trash')
		$product->set_status(isset($args['status']) ? $args['status'] : 'publish');

		// Visibility ('hidden', 'visible', 'search' or 'catalog')
		$product->set_catalog_visibility(isset($args['visibility']) ? $args['visibility'] : 'visible');

		// Featured (boolean)
		$product->set_featured(isset($args['featured']) ? $args['featured'] : false);

		// Virtual (boolean)
		$product->set_virtual(isset($args['virtual']) ? $args['virtual'] : false);

		// Prices
		$product->set_regular_price($args['regular_price']);
		$product->set_sale_price(isset($args['sale_price']) ? $args['sale_price'] : '');
		$product->set_price(isset($args['sale_price']) ? $args['sale_price'] : $args['regular_price']);

		if (isset($args['sale_price'])) {
			$product->set_date_on_sale_from(isset($args['sale_from']) ? $args['sale_from'] : '');
			$product->set_date_on_sale_to(isset($args['sale_to']) ? $args['sale_to'] : '');
		}

		// Downloadable (boolean)
		$product->set_downloadable(isset($args['downloadable']) ? $args['downloadable'] : false);
		if (isset($args['downloadable']) && $args['downloadable']) {
			$product->set_downloads(isset($args['downloads']) ? $args['downloads'] : []);
			$product->set_download_limit(isset($args['download_limit']) ? $args['download_limit'] : '-1');
			$product->set_download_expiry(isset($args['download_expiry']) ? $args['download_expiry'] : '-1');
		}

		// Taxes
		if (get_option('woocommerce_calc_taxes') === 'yes') {
			$product->set_tax_status(isset($args['tax_status']) ? $args['tax_status'] : 'taxable');
			$product->set_tax_class(isset($args['tax_class']) ? $args['tax_class'] : '');
		}

		// SKU and Stock (Not a virtual product)
		if (!isset($args['virtual']) || (isset($args['virtual']) && !$args['virtual'])) {
			$product->set_sku(isset($args['sku']) ? $args['sku'] : '');
			$product->set_manage_stock(isset($args['manage_stock']) ? $args['manage_stock'] : false);
			$product->set_stock_status(isset($args['stock_status']) ? $args['stock_status'] : 'instock');
			if (isset($args['manage_stock']) && $args['manage_stock']) {
				$product->set_stock_status($args['stock_qty']);
				$product->set_backorders(isset($args['backorders']) ? $args['backorders'] : 'no'); // 'yes', 'no' or 'notify'
			}
		}

		// Sold Individually
		$product->set_sold_individually(isset($args['sold_individually']) ? $args['sold_individually'] : false);

		// Weight, dimensions and shipping class
		$product->set_weight(isset($args['weight']) ? $args['weight'] : '');
		$product->set_length(isset($args['length']) ? $args['length'] : '');
		$product->set_width(isset($args['width']) ? $args['width'] : '');
		$product->set_height(isset($args['height']) ? $args['height'] : '');
		if (isset($args['shipping_class_id'])) {
			$product->set_shipping_class_id($args['shipping_class_id']);
		}

		// Upsell and Cross sell (IDs)
		$product->set_upsell_ids(isset($args['upsells']) ? $args['upsells'] : '');
		$product->set_cross_sell_ids(isset($args['cross_sells']) ? $args['upsells'] : '');

		// Attributes et default attributes
		if (isset($args['attributes'])) {
			$product->set_attributes(self::prepareProductAttributes($args['attributes']));
		}
		if (isset($args['default_attributes'])) {
			$product->set_default_attributes($args['default_attributes']);
		} // Needs a special formatting

		// Reviews, purchase note and menu order
		$product->set_reviews_allowed(isset($args['reviews']) ? $args['reviews'] : false);
		$product->set_purchase_note(isset($args['note']) ? $args['note'] : '');
		if (isset($args['menu_order'])) {
			$product->set_menu_order($args['menu_order']);
		}

		// Product categories and Tags
		if (isset($args['category_ids'])) {
			$product->set_category_ids('category_ids');
		}
		if (isset($args['tag_ids'])) {
			$product->set_tag_ids($args['tag_ids']);
		}

		// Images and Gallery
		if (isset($args['images']) && is_array($args['images'])) {
			$image_id = Attachment::assignRemoteAttachment($args['images'][0]);
			$product->set_image_id($image_id ? $image_id : "");
		}

		$gallery_ids = [];

		if (count($args['images']) > 1) {
			array_shift($args['images']);
			foreach ($args['images'] as $img) {
				$gallery_ids[] = Attachment::assignRemoteAttachment($img);
			}
		}

		$product->set_gallery_image_ids($gallery_ids);

		// Save product
		$product_id = $product->save();

		// Create each variations
		if ($args['type'] === 'variable') {
			foreach ($args['variations'] as $variation) {
				self::createVariation($product_id, $variation);
			}
		}

		return $product_id;
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
		$variation_id = self::getProductIdFromSku($variation_data['sku']);

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

	/**
	 * Create product attributes if needed
	 * @access public
	 * @param $attributes | Product attributes
	 * @return array | Final attributes array
	 */
	protected static function prepareProductAttributes(array $attributes): array
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

	/**
	 * Return the correct object according to product type
	 *
	 * @access protected
	 * @param string $type | Type of product
	 * @param int $product_id | ID of the product
	 * @return WC_Product_External|\WC_Product|WC_Product_Grouped|WC_Product_Simple|WC_Product_Variable
	 */
	protected static function getProductObjectType(string $type, int $product_id = 0) : object
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
