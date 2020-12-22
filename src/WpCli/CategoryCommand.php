<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\CategoryAdapter;
use AmphiBee\AkeneoConnector\DataPersister\CategoryPersister;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use WP_CLI;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AkeneoCategory;

class CategoryCommand
{
    /**
     * Returns 'Hello World'
     *
     * @since  0.0.1
     * @author Scott Anderson
     */
    public function import()
    {
        WP_CLI::warning('Import OK');
        LoggerService::log(Logger::DEBUG, 'Starting category import');

        $categoryApi = AkeneoClientBuilder::create()->getCategoryProvider();
        $categoryAdapter = new CategoryAdapter();
        $categoryPersister = new CategoryPersister();

        /** @var AkeneoCategory $category */
        foreach ($categoryApi->getAll() as $AknCategory) {
            LoggerService::log(Logger::DEBUG, sprintf('Running CatCode: %s', $AknCategory->getCode()));

            $wooCommerceCategory = $categoryAdapter->getWordpressCategory($AknCategory);
            $categoryPersister->createOrUpdateCategory($wooCommerceCategory);
        }

        LoggerService::log(Logger::DEBUG, 'Ending category import');
        WP_CLI::success('Import OK');
    }
}
