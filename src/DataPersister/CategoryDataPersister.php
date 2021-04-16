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
    private static $parent_ignore = [];
    /**
     * @param Category $category
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateCategory(Category $category): void
    {
        try {

            // @todo dynamiser
            $base_category = '2_default_category';

            $catAsArray = $this->getSerializer()->normalize($category);

            $termId = $this->findCategoryByAkeneoCode($category->getName());

            if (
                ($catAsArray['parent'] === 'root_category' && $catAsArray['name'] !== $base_category) ||
                in_array($catAsArray['parent'], self::$parent_ignore)
            ) {
                self::$parent_ignore[] = $catAsArray['name'];
                return;
            }

            // don't import base cat
            if ($catAsArray['parent'] === 'root_category') {
                return;
            }

            // @todo implement polylang
            $language = 'fr_FR';
            $termName = $catAsArray['labels'][$language];
            $akeneoCode = $catAsArray['name'];

            $catAsArray['parent'] = $catAsArray['parent'] === 'root_category' ? 0 : $this->findCategoryByAkeneoCode($catAsArray['parent']);

            unset($catAsArray['labels']);
            unset($catAsArray['name']);

            if ($termId > 0 || term_exists($termName, 'product_cat')) {
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
                $termId = $term['term_id'];
            }
            update_term_meta($termId, '_akeneo_code', $akeneoCode);

            do_action('ak/category/after_save', $termId, $catAsArray);

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

        if ($term_query->terms === null) {
            return 0;
        }

        return count($term_query->terms) > 0 ? $term_query->terms[0] : 0;
    }
}
