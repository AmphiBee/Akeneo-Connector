<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\DataProvider\FamilyVariantDataProvider;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Models\ProductModel;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Model as WP_Model;
use AmphiBee\AkeneoConnector\DataPersister\Concerns\CreatesProducts;

class ModelDataPersister extends AbstractDataPersister
{
    use CreatesProducts;

    /**
     * @param WP_Model $model
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdate(WP_Model $model): void
    {
        try {
            $ids = [];

            $available_locales = $this->translator->available;

            foreach ($available_locales as $locale) {
                $slug = $this->translator->localeToSlug($locale);

                # Set current lang context, to avoid unwanted translations by Polylang/WPML
                $this->translator->setCurrentLang($slug);

                # If this is the primary language, save the product model relationship
                $save_relationship = !$this->translator->active() || $this->translator->default === $locale;

                # Save ids in array to sync them as translation of each others
                $ids[$slug] = $this->updateSingleModel($model, $locale, $save_relationship);
            }

            $ids = array_filter($ids);

            # Set terms as translation of each others
            if (count($ids) > 1) {
                $this->translator->syncPosts($ids);
            }

            # catch error
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Model (ModelCode %s) %s',
                print_r($model, true),
                $e->getMessage()
            ));

            return;
        }
    }


    /**
     * Save a single model by locale.
     *
     * @param WP_Model  $model              The model entity.
     * @param string    $locale             The locale we are storing.
     * @param bool      $save_relationship  Weither we should save the relationship status or not (only needed once per language).
     *
     * @return int The stored product id
     */
    public function updateSingleModel(WP_Model $model, string $locale, bool $save_relationship)
    {
        /**
         * When models have parents, it means we only get to store
         * the children's variable attribute for the parent WC variable product
         */
        if ($model->getParent()) {
            $relationship = $this->storeProductModelRelationship($model, 0);
            return $relationship ? $relationship->product_id : 0;
        }

        $product = [
            'status' => 'draft',
            'type'   => 'variable',
        ] + static::$base_product;

        $product_id = $this->updateSingleElement($product, $model, $locale);

        # Only saving the relation ship on the primary language
        if ($save_relationship) {
            $this->storeProductModelRelationship($model, $product_id);
        }

        return $product_id;
    }


    /**
     * Store relationship between an Akeneo Product model and a WooCommerce Variable product.
     * A WooCommerce Variable product may have multiple Product models, which all represent a single variable attribute.
     *
     * @return ProductModel
     */
    protected function storeProductModelRelationship(WP_Model $model, int $product_id = 0)
    {
        if ($parent = ($model->getParent() ?: null)) {
            $parent = ProductModel::where('model_code', $parent)->first();
        }

        if ($parent && !$product_id) {
            $product_id = $parent->product_id;
        }

        return ProductModel::updateOrCreate([
            'product_id'   => $product_id,
            'parent_id'    => $parent ? $parent->id : null,
            'model_code'   => $model->getCode(),
            'family_code' => $model->getFamily(),
            'variant_code' => $model->getFamilyVariant(),
        ]);
    }


    /**
     * After running the models import, we need to add variant attributes to the added variable products.
     *
     * @return void
     */
    public function setupVariationAttributes(): void
    {
        $models = ProductModel::get()->groupBy('product_id');

        $models->each(function ($models, $product_id) {
            # get product or skip
            if (!$product_id || !($wp_product = wc_get_product($product_id))) {
                return;
            }

            $attributes = $models->mapWithKeys(function ($model) {
                $code = $this->getCodeFromModel($model);
                return [$code => $this->formatVariableAttribute($code)];
            })->toArray();

            $attributes = $this->prepareProductAttributes($attributes);
            $old        = $wp_product->get_attributes();

            # forbid dupliquates
            foreach ($attributes as $attr_key => $attr) {
                if (array_key_exists($attr_key, $old)) {
                    if (isset($old[$attr_key])) {
                        $attr->set_options(array_merge(
                            $old[$attr_key]->get_options() ?: [],
                            $attr->get_options() ?: []
                        ));
                        unset($old[$attr_key]);
                    }
                }
            }

            # reorder and merge
            $position = count($attributes);
            foreach ($old as $key => $val) {
                $val->set_position($position);
                $attributes[$key] = $val;
                $position++;
            }

            # replace old attribute by new merged attributes
            $wp_product->set_attributes($attributes);
            $wp_product->save();
        });
    }
}
