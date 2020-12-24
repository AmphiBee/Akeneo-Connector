<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\ModelAdapter;
use AmphiBee\AkeneoConnector\DataPersister\ModelDataPersister;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AkeneoCategory;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use WP_CLI;

class ProductModelCommand
{
    public function import(): void
    {
        WP_CLI::warning('Import Started');
        LoggerService::log(Logger::DEBUG, 'Starting product model import');

        $modelProvider = AkeneoClientBuilder::create()->getProductModelProvider();
        $modelAdapter = new ModelAdapter();
        $modelPersister = new ModelDataPersister();

        /** @var AkeneoCategory $category */
        foreach ($modelProvider->getAll() as $AknModel) {
            LoggerService::log(Logger::DEBUG, sprintf('Running ProductModel Code: %s', $AknModel->getCode()));

            $wooCommerceModel = $modelAdapter->getWordpressModel($AknModel);
            $modelPersister->createOrUpdateModel($wooCommerceModel);
        }

        LoggerService::log(Logger::DEBUG, 'Ending product model import');
        WP_CLI::success('Import OK');
    }
}
