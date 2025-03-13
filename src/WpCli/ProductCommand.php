<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\ProductAdapter;
use AmphiBee\AkeneoConnector\DataPersister\ProductDataPersister;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class ProductCommand extends AbstractCommand
{
    public static string $name = 'products';

    public static string $desc = 'Supports Akaneo Products import (including product variations)';

    public static string $long_desc = '';

    /**
     * Run the import command.
     */
    public function import(): void
    {
        # Debug
        $this->print('Starting product import');

        $provider  = AkeneoClientBuilder::create()->getProductProvider();
        $familyVariantDataProvider = AkeneoClientBuilder::create()->getFamilyVariantProvider();
        $adapter   = new ProductAdapter();
        $persister = new ProductDataPersister($familyVariantDataProvider);

        do_action('ak/a/products/before_import', $provider->getAll());

        # Allow duplicate SKUs, for translations to work properly
        add_filter('wc_product_has_unique_sku', '__return_false');

        $products = (array) apply_filters('ak/f/products/import_data', iterator_to_array($provider->getAll()));

        # Statistiques d'import
        $stats = [
            'total' => count($products),
            'imported' => 0,
            'skipped_disabled' => 0,
            'skipped_unchanged' => 0,
            'errors' => 0
        ];

        foreach ($products as $ak_product) {
            $enabled = $ak_product->isEnabled();
            $code = $ak_product->getIdentifier();

            $this->print(sprintf('Running Product with code: %s, [ Enabled: %s ]', $code, $enabled ? 'Yes' : 'No'));

            try {
                if (!$enabled) {
                    $this->print(sprintf('Skipping disabled product %s', $code), 'line');
                    $stats['skipped_disabled']++;
                    continue;
                }
                
                $wp_product = $adapter->fromProduct($ak_product);
                
                // Vérifier si le produit a changé avant de l'importer
                $product_id = Fetcher::getProductIdBySku($wp_product->getCode(), $persister->getDefaultLocale());
                
                if ($product_id) {
                    $stored_hash = get_post_meta($product_id, '_akeneo_hash', true);
                    $current_hash = $wp_product->getHash();
                    
                    if ($stored_hash && $stored_hash === $current_hash) {
                        $this->print(sprintf('Skipping product %s - No changes detected', $code), 'line');
                        $stats['skipped_unchanged']++;
                        continue;
                    }
                }
                
                $persister->createOrUpdate($wp_product);
                $stats['imported']++;
            } catch (\Exception $e) {
                $this->error('An error occurred while creating the product : ' . $e->getMessage() . "(". $e->getCode() .")");
                $stats['errors']++;
            }
        }

        do_action('ak/a/products/after_import', $provider->getAll());
        do_action('ak/product/after_import', $provider->getAll()); # backwards compatibility
        
        $this->print(sprintf(
            'Import completed: %d products processed, %d imported, %d skipped (disabled), %d skipped (unchanged), %d errors',
            $stats['total'],
            $stats['imported'],
            $stats['skipped_disabled'],
            $stats['skipped_unchanged'],
            $stats['errors']
        ), 'success');
    }
}
