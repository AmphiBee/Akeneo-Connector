<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use Monolog\Logger;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;
use AmphiBee\AkeneoConnector\Models\ProductModel;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\DataPersister\Concerns\CreatesProducts;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Product as WP_Product;

class ProductDataPersister extends AbstractDataPersister
{
    use CreatesProducts;

    /**
     * @param WP_Product $product
     */
    public function createOrUpdate(WP_Product $product): void
    {
        try {
            if (!$product->isEnabled()) {
                return;
            }

            # Product with product model (variable product in WC)
            if ($product->getParent()) {
                $this->createOrUpdateProductVariable($product);
            }
            # Simple product
            else {
                $this->createOrUpdateProductSimple($product);
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
     * Single simple product.
     *
     * @param Product $product
     */
    protected function createOrUpdateProductSimple(WP_Product $product): void
    {
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

    }


    /**
     * Single variable product.
     *
     * @param WP_Product $product
     */
    protected function createOrUpdateProductVariable(WP_Product $product): void
    {
        $model  = ProductModel::where('model_code', $product->getParent())->first();
        $locale = $this->translator->default;

        # We need the product model to continue.
        if (!$model) {
            throw new \Exception(sprintf('The product model with code `%s` could not be found. Skipping product with code `%s`.', $product->getParent(), $product->getCode()));
        }

        $wc_product = wc_get_product($model->product_id);

        /**
         * TODO : check if variation already exists
         * TODO : support multilingual
         */
        $variation_id = \wp_insert_post([
            'post_title'  => $wc_product->get_name(),
            'post_name'   => 'product-'.$model->product_id.'-variation',
            'post_status' => 'publish',
            'post_parent' => $model->product_id,
            'post_type'   => 'product_variation',
            'guid'        => $wc_product->get_permalink(),
            'meta_input'  => [
                '_akeneo_code' => $product->getCode(),
            ],
        ]);

        # Get an instance of the WC_Product_Variation object
        $variation = new \WC_Product_Variation($variation_id);

        /**
         * Recursively get variant(s)
         */
        $md         = $model;
        $attributes = [];

        do {
            $attributes[] = $md->variant_code;
            $md = $md->parent;
        } while ($md);


        $values = $product->getValues();

        foreach ($attributes as $attribute) {
            $val = $values[$attribute][0]['data'] ?? '';
            $taxonomy = strtolower('pa_' . $attribute);
            $term_id = Fetcher::getTermIdByAkeneoCode($val, $taxonomy, $locale);

            if (!$term_id) {
                return; # Missing attribute term, skip this variation.
            }

            $term = get_term($term_id);

            # Make sure the term is allowed in the variable product
            $post_attr_terms = wp_get_post_terms($model->product_id, $taxonomy);

            if (!in_array($term, $post_attr_terms)) {
                wp_set_post_terms($model->product_id, [$term->term_id], $taxonomy, true);
            }

            update_post_meta($variation_id, 'attribute_'.$taxonomy, $term->slug);
        }

        $variation->set_sku($product->getCode());

        /*

        // Prices
        if( empty( $variation_data['sale_price'] ) ){
            $variation->set_price( $variation_data['regular_price'] );
        } else {
            $variation->set_price( $variation_data['sale_price'] );
            $variation->set_sale_price( $variation_data['sale_price'] );
        }
        $variation->set_regular_price( $variation_data['regular_price'] );

        // Stock
        if( ! empty($variation_data['stock_qty']) ){
            $variation->set_stock_quantity( $variation_data['stock_qty'] );
            $variation->set_manage_stock(true);
            $variation->set_stock_status('');
        } else {
            $variation->set_manage_stock(false);
        }

         $variation->set_weight(''); // weight (reseting)

        */

        $variation->save(); // Save the data
    }



    /**
     * Save a single product by locale.
     *
     * @param WP_Product  $product  The product entity.
     * @param string      $locale   The locale we are storing.
     *
     * @return int The stored product id
     */
    public function updateSingleProduct(WP_Product $product, string $locale)
    {
        return $this->updateSingleElement(static::$base_product, $product, $locale);
    }
}
