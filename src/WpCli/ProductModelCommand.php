<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\ModelAdapter;
use AmphiBee\AkeneoConnector\DataPersister\ModelDataPersister;
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
class ProductModelCommand extends AbstractCommand
{
    public static string $name = 'models';

    public static string $desc = 'Supports Akaneo Product Models import';

    public static string $long_desc = '';


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

        $models = apply_filters('ak/f/product_models/import_data', iterator_to_array($provider->getAll()));

        foreach ($models as $ak_model) {
            $this->print(sprintf('Running Product Model with code: %s', $ak_model->getCode()));

            $wp_model = $adapter->fromModel($ak_model);
            $persister->createOrUpdate($wp_model);
        }

        do_action('ak/a/product_models/after_import', $provider->getAll());
    }
}
