<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\DataProvider\FamilyVariantDataProvider;
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
     * @param FamilyVariantDataProvider $familyVariantDataProvider
     * @param WP_Product                $product
     *
     * @return void
     * @throws \Exception
     */
    public function createOrUpdate(WP_Product $product): void
    {
        try {
            if (!$product->isEnabled()) {
                return;
            }
            
            // Vérifier si le produit existe déjà et si son hash a changé
            $product_id = Fetcher::getProductIdBySku($product->getCode(), $this->translator->default);
            
            if ($product_id) {
                $stored_hash = get_post_meta($product_id, '_akeneo_hash', true);
                $current_hash = $product->getHash();
                
                // Si le hash est identique, on peut sauter l'import
                if ($stored_hash && $stored_hash === $current_hash) {
                    LoggerService::log(Logger::INFO, sprintf(
                        'Skipping product import for code %s - No changes detected',
                        $product->getCode()
                    ));
                    return;
                }
            }

            if ($product->getParent()) {
                # Product with product model (variable product in WC)
                $this->createOrUpdateProductVariable($product);
            } else {
                # Simple product
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
        $model = $md = ProductModel::where('model_code', $product->getParent())->first();

        # We need the product model to continue.
        if (!$model) {
            throw new \Exception(sprintf('The product model with code `%s` could not be found. Skipping product with code `%s`.', $product->getParent(), $product->getCode()));
        }

        # Build up the translation array for variation creation
        if ($this->translator->active()) {
            $translations = $this->translator->getPostIds($model->product_id);
        } else {
            $translations = [
                $this->translator->default => $model->product_id,
            ];
        }

        # Hierarchical loop to get variant(s) from models
        $attributes = [];
        do {
            $code = $this->getCodeFromModel($model);

            $attributes[] = $code;

            $md = $md->parent;
        } while ($md);

        # Register the translation for each translation of the product
        foreach ($translations as $locale => $product_id) {
            $this->makeVariation($product_id, $product, $attributes, $locale);
        }
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

    public function getFamilyVariantDataProvider(): FamilyVariantDataProvider
    {
        return $this->familyVariantDataProvider;
    }
}
