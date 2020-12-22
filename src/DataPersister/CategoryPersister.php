<?php
namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class CategoryPersister extends AbstractDataPersister
{
    /**
     * @param Category $category
     */
    public function createOrUpdateCategory(Category $category): void
    {
        try {
            $catAsArray = $this->getSerializer()->normalize($category);
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize category (Category Code %s) %s',
                print_r($category, true),
                $e->getMessage()
            ));

            return;
        }

        var_dump($catAsArray);
        //@todo implement Wordpress logic
    }
}
