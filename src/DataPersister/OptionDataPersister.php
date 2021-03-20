<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\DataProvider\AttributeDataProvider;
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
            $optionLabels = $option->getLabels();
            // @todo implement polylang
            $language = 'fr_FR';
            $optionLabel = $optionLabels[$language];
            $optionAttribute = $option->getAttribute();
            $mapping = Settings::getMappingValue($optionAttribute);
            $attributeLabel = 'pa_' . strtolower($option->getAttribute());

            if ($mapping !== 'global_attribute') {
                return;
            }

            if (!taxonomy_exists($attributeLabel)) {
                return;
            }

            $termId = $option->findOptionByAkeneoCode($attributeLabel);

            $optionArgs = [];

            if (term_exists($optionLabel, $attributeLabel) || $termId > 0) {
                $optionArgs['name'] = $optionLabel;
                \wp_update_term(
                    $termId,
                    $attributeLabel,
                    $optionArgs
                );
            } else {
                $term = \wp_insert_term(
                    $optionLabel,
                    $attributeLabel,
                    $optionArgs
                );
                $termId = $term['term_id'];
            }

            update_term_meta($termId, '_akeneo_code', $optionCode);
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Option (OptCode %s) %s',
                print_r($option, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
