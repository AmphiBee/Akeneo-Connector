<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\ProductAdapter;
use AmphiBee\AkeneoConnector\DataPersister\ProductDataPersister;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;

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
        $adapter   = new ProductAdapter();
        $persister = new ProductDataPersister();

        do_action('ak/a/products/before_import', $provider->getAll());

        # Allow duplicate SKUs, for translations to work properly
        add_filter('wc_product_has_unique_sku', '__return_false');

        foreach ($provider->getAll() as $ak_product) {
            $enabled = $ak_product->isEnabled();

            $this->print(sprintf('Running Product with code: %s, [ Enabled: %s ]', $ak_product->getIdentifier(), $enabled ? 'Yes' : 'No, skipping'));

            $wp_product = $adapter->fromProduct($ak_product);
            $persister->createOrUpdate($wp_product);
        }

        do_action('ak/a/products/after_import', $provider->getAll());
        do_action('ak/product/after_import', $provider->getAll()); # backwards compatibility
    }
}
