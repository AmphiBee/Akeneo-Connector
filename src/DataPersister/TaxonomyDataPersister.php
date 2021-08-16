<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class TaxonomyDataPersister extends AbstractDataPersister
{
    protected $taxonomy = '';

    /**
     * Construct the taxonomy data persister.
     *
     * @param string $taxonomy The Wordpress taxonomy.
     */
    public function __construct(string $taxonomy)
    {
        $this->taxonomy = $taxonomy;

        parent::__construct();
    }


    /**
     * @param mixed        $tag             Can be Tag, Attribute..
     * @param false|string $force_parent    The parent to force-use. Can be Akaneo Code.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdate(object $item, $force_parent = false): void
    {
        try {
            $itemAsArray = $this->getSerializer()->normalize($item);

            $termId      = $this->findTermByAkeneoCode($item->getName());

            // @todo implement polylang
            $language   = 'fr_FR';
            $termName   = $itemAsArray['labels'][$language];
            $akeneoCode = $itemAsArray['name'];

            // Force parent if not specified by Akaneo
            if ($force_parent !== false && is_string($force_parent)) {
                $itemAsArray['parent'] = $force_parent;
            }

            $itemAsArray['parent'] = $itemAsArray['parent'] === 'root_category' ? 0 : $this->findTermByAkeneoCode($itemAsArray['parent']);

            unset($itemAsArray['labels']);
            unset($itemAsArray['name']);

            if ($termId > 0 || term_exists($termName, $this->taxonomy)) {
                $itemAsArray['name'] = $termName;
                \wp_update_term(
                    $termId,
                    $this->taxonomy,
                    $itemAsArray
                );
            } else {
                $term = \wp_insert_term(
                    $termName,
                    $this->taxonomy,
                    $itemAsArray
                );
                $termId = $term['term_id'];
            }

            update_term_meta($termId, '_akeneo_code', $akeneoCode);

            do_action('ak/tag/after_save', $termId, $akeneoCode, $itemAsArray);
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize item ([%s], Code %s) %s',
                get_class($item),
                print_r($item, true),
                $e->getMessage()
            ));

            return;
        }
    }

    public function findTermByAkeneoCode($akeneoCode) : int
    {
        $args = [
            'hide_empty'    => false,
            'fields'        => 'ids',
            'taxonomy'      => $this->taxonomy,
            'meta_query'    => [
                'relation'  => 'AND',
                [
                    'key'   => '_akeneo_code',
                    'value' => $akeneoCode,
                ]
            ]
        ];

        $term_query = new \WP_Term_Query($args);

        if ($term_query->terms === null) {
            return 0;
        }

        return count($term_query->terms) > 0 ? $term_query->terms[0] : 0;
    }
}
