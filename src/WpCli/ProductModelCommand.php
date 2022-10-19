<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Models\ProductModel;
use AmphiBee\AkeneoConnector\Adapter\ModelAdapter;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\DataPersister\ModelDataPersister;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @version    1.13.0
 * @access     public
 */
class ProductModelCommand extends AbstractCommand
{
    public static string $name = 'models';

    public static string $desc = 'Supports Akaneo Product Models import';

    public static string $long_desc = 'Product models are stored as WooComerce variable products.';


    /**
     * Run the import command.
     */
    public function import(): void
    {
        # Debug
        $this->print('Starting product model import');

        $provider  = AkeneoClientBuilder::create()->getProductModelProvider();
        $adapter   = new ModelAdapter();
        $persister = new ModelDataPersister();

        do_action('ak/a/product_models/before_import', $provider->getAll());

        # Make sure to import models without parent first
        $models = $this->orderModelsBeforeImport(
            iterator_to_array($provider->getAll())
        );

        $models = (array) apply_filters('ak/f/product_models/import_data', $models);

        # Clear product models from database first.
        ProductModel::truncate();

        foreach ($models as $ak_model) {
            $this->print(sprintf('Running Product Model with code: %s', $ak_model->getCode()));

            $wp_model = $adapter->fromModel($ak_model);
            $persister->createOrUpdate($wp_model);
        }

        # Add variant attributes to the created variable products
        $persister->setupVariationAttributes();

        do_action('ak/a/product_models/after_import', $provider->getAll());

        $this->print('Models imported successfully');
    }


    /**
     * Sort the models so we import parents first, to avoid missing product_ids
     *
     * @return array
     */
    protected function orderModelsBeforeImport($models)
    {
        return collect($models)->sort(function ($a, $b) {
            $a = $a->getParent();
            $b = $b->getParent();

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        })->toArray();
    }
}
