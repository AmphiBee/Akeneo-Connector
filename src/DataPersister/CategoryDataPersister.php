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
            $termId = $this->findCategoryByAkeneoCode($category->getName());

            // @todo implement polylang
            $language = 'fr_FR';
            $termName = $catAsArray['labels'][$language];
            $akeneoCode = $catAsArray['name'];

            $catAsArray['parent'] = $catAsArray['parent'] === 'root_category' ? 0 : $this->findCategoryByAkeneoCode($catAsArray['parent']);
            unset($catAsArray['labels']);
            unset($catAsArray['name']);

            if ($termId > 0) {
                $catAsArray['name'] = $termName;
                \wp_update_term(
                    $termId,
                    'product_cat',
                    $catAsArray
                );
            } else {
                $term = \wp_insert_term(
                    $termName,
                    'product_cat',
                    $catAsArray
                );
                update_term_meta($term['term_id'], '_akeneo_code', $akeneoCode);
            }
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize category (Category Code %s) %s',
                print_r($category, true),
                $e->getMessage()
            ));

            return;
        }
    }

    public function findCategoryByAkeneoCode($akeneoCode) : int
    {
        $args = [
            'hide_empty'    => false,
            'fields'        => 'ids',
            'taxonomy'      => 'product_cat',
            'meta_query'    => [
                'relation'  => 'AND',
                [
                    'key'   => '_akeneo_code',
                    'value' => $akeneoCode,
                ]
            ]
        ];
        $term_query = new \WP_Term_Query( $args );

        return count($term_query->terms) > 0 ? $term_query->terms[0] : 0;
    }
}
