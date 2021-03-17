<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Admin\Settings;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Option;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class OptionDataPersister extends AbstractDataPersister
{
    /**
     * @param Option $option
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateOption(Option $option): void
    {
        try {
            $optionCode = $option->getCode();
            $optionName = $option->getName();
            $optionAttribute = $option->getAttribute();
            $mapping = Settings::getMappingValue($optionAttribute);

            if ($mapping !== 'global_attribute') {
                return;
            }

            var_dump($option);
            die();

            $termId = $this->findOptionByAkeneoCode($optionCode, $optionAttribute);

            $optionArgs = [];
            var_dump($optionAttribute);
            var_dump($optionName);
            if ($termId > 0) {
                $optionArgs['name'] = $optionName;
                \wp_update_term(
                    $termId,
                    'product_cat',
                    $optionArgs
                );
            } else {
                $term = \wp_insert_term(
                    $optionName,
                    "pa_{$optionAttribute}",
                    $optionArgs
                );
                update_term_meta($term['term_id'], '_akeneo_code', $optionCode);
            }

            die();

        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Option (OptCode %s) %s',
                print_r($option, true),
                $e->getMessage()
            ));

            return;
        }
    }

    public function findOptionByAkeneoCode($akeneoCode, $attributeName) : int
    {
        $args = [
            'hide_empty'    => false,
            'fields'        => 'ids',
            'taxonomy'      => "pa_{$attributeName}",
            'meta_query'    => [
                'relation'  => 'AND',
                [
                    'key'   => '_akeneo_code',
                    'value' => $akeneoCode,
                ]
            ]
        ];
        $term_query = new \WP_Term_Query( $args );

        return is_array($term_query->terms) && count($term_query->terms) > 0 ? $term_query->terms[0] : 0;
    }
}
