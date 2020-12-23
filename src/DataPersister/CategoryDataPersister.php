<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class CategoryDataPersister extends AbstractDataPersister
{
    /**
     * @param Category $category
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateCategory(Category $category): void
    {
        try {
            $catAsArray = $this->getSerializer()->normalize($category);
            //@todo save in WC
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize category (Category Code %s) %s',
                print_r($category, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
