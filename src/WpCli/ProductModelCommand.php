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
    public function import(array $args, array $assocArgs): void
    {
        $skus = $assocArgs['skus']?? [];

        $provider  = AkeneoClientBuilder::create()->getProductModelProvider();

        if (!empty($skus)) {
            $this->print('Importing product models for SKUs: '. $skus);
            $items = $provider->getProductModelsByCodes(explode(',', $skus));
        } else {
            $this->print('Starting product model import');
            $items = $provider->getAll();
        }

        $familyVariantDataProvider = AkeneoClientBuilder::create()->getFamilyVariantProvider();
        $adapter   = new ModelAdapter();
        $persister = new ModelDataPersister($familyVariantDataProvider);

        do_action('ak/a/product_models/before_import', $items);

        # Make sure to import models without parent first
        $models = $this->orderModelsBeforeImport(
            iterator_to_array($items)
        );

        $models = (array) apply_filters('ak/f/product_models/import_data', $models);

        # Statistiques d'import
        $stats = [
            'total' => count($models),
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        foreach ($models as $ak_model) {
            $this->print(sprintf('Running Product Model with code: %s', $ak_model->getCode()));
            try {
                $wp_model = $adapter->fromModel($ak_model);

                // Vérifier si le modèle a changé avant de l'importer
                $currentHash = $persister->generateModelHash($wp_model);
                $existingModel = ProductModel::where('model_code', $wp_model->getCode())->first();

                if ($existingModel && $existingModel->hash === $currentHash) {
                    $this->print(sprintf('Skipping model %s - No changes detected', $wp_model->getCode()), 'line');
                    $stats['skipped']++;
                    continue;
                }

                $persister->createOrUpdate($wp_model);
                $stats['imported']++;
            } catch (\Exception $e) {
                $this->error('An error occurred while creating the product : ' . $e->getMessage() . "(" . $e->getCode() . ")");
                $stats['errors']++;
            }
        }

        # Add variant attributes to the created variable products
        $persister->setupVariationAttributes();

        do_action('ak/a/product_models/after_import', $provider->getAll());

        $this->print(sprintf(
            'Import completed: %d models processed, %d imported, %d skipped, %d errors',
            $stats['total'],
            $stats['imported'],
            $stats['skipped'],
            $stats['errors']
        ), 'success');
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
