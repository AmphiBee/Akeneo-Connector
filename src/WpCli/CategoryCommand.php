<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\CategoryAdapter;
use AmphiBee\AkeneoConnector\DataPersister\CategoryDataPersister;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use WP_CLI;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AkeneoCategory;

class CategoryCommand
{
    public function import(): void
    {
        WP_CLI::warning('Import OK');
        LoggerService::log(Logger::DEBUG, 'Starting category import');

        $categoryDataProvider = AkeneoClientBuilder::create()->getCategoryProvider();
        $categoryAdapter = new CategoryAdapter();
        $categoryPersister = new CategoryDataPersister();

        /** @var AkeneoCategory $category */
        foreach ($categoryDataProvider->getAll() as $AknCategory) {
            LoggerService::log(Logger::DEBUG, sprintf('Running CatCode: %s', $AknCategory->getCode()));

            $wooCommerceCategory = $categoryAdapter->getWordpressCategory($AknCategory);
            var_dump($AknCategory);
            var_dump('ok');die;
            $categoryPersister->createOrUpdateCategory($wooCommerceCategory);
        }

        LoggerService::log(Logger::DEBUG, 'Ending category import');
        WP_CLI::success('Import OK');
    }
}
