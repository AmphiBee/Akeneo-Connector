<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use Monolog\Logger;
use OP\Lib\WpEloquent\Model\Term;
use OP\Lib\WpEloquent\Model\Meta\TermMeta;
use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;
use AmphiBee\AkeneoConnector\Facade\LocaleStrings;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AttributeDataPersister extends AbstractDataPersister
{
    /**
     * @param Attribute $attribute
     */
    public function importBooleanAttributeOption($attr): void
    {
        try {
            $code     = $attr->getCode();
            $mapping  = Settings::getMappingValue($code);
            $lang     = $this->translator;
            $original = null;

            if ($mapping !== 'private_global_attribute' || $mapping !== 'global_attribute' || $attr->getType() !== 'pim_catalog_boolean') {
                return;
            }

            $taxonomy = strtolower("pa_{$code}");

            // TODO: read mo/php lang file to get current language booleans translations
            $choices = [
                'true'  => 'Yes',
                'false' => 'No',
            ];

            # Make sure to start with default language first
            $locales = [$lang->default] + array_diff($lang->available, [$lang->default]);

            foreach ($choices as $val => $choice) {
                LocaleStrings::register($val, $choice);

                foreach ($locales as $i => $locale) {
                    $term_name = $i === 0 ? $choice : $lang->getStringIn($choice, 'Akeneo Options', $locale);

                    # Get term for current language
                    $term = Fetcher::getTermBooleanByAkeneoCode($val, $taxonomy, $locale);

                    # Not exists, create
                    if (!$term) {
                        $inserted = wp_insert_term($term_name, $taxonomy);
                        $inserted = (is_array($inserted) && isset($inserted['term_id'])) ? $inserted['term_id'] : null;

                        if (!$inserted) {
                            LoggerService::log(Logger::ERROR, sprintf('Cannot create term %s for %s taxonomy (locale: %s)', $term_name, $taxonomy, $locale));
                            continue;
                        }

                        $term = Term::findOrFail($inserted);

                        $term->meta()->saveMany([
                            TermMeta::updateSingle('_akeneo_opt_boolean', $val, $term->id),
                            TermMeta::updateSingle('_akeneo_lang', $locale, $term->id),
                        ]);
                    }

                    if ($i === 0) {
                        $original = $term;
                    }

                    # Sync post as translations of each other
                    if ($original->id !== $term->id) {
                        $sync = $lang->getTermTranslations($original->id);
                        $sync[$lang->localeToSlug($locale)] = $term->id;

                        $lang->syncTerms($sync);
                    }
                }
            }
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Attribute (Attr Code %s) %s',
                print_r($attr, true),
                $e->getMessage()
            ));
            return;
        }
    }


    /**
     * @param array $attribute [code, name, type, locale]
     */
    public function createOrUpdateFromArray(array $attribute)
    {
        try {
            $mapping = Settings::getMappingValue($attribute['code']);

            if (!($mapping === 'global_attribute' || $mapping === 'private_global_attribute')) {
                return;
            }

            $attributes = \wc_get_attribute_taxonomies();
            $slugs      = \wp_list_pluck($attributes, 'attribute_name');

            if (! in_array($attribute['code'], $slugs)) {
                $args = array(
                    'slug'         => $attribute['code'],
                    'name'         => $attribute['name'],
                    'type'         => 'select',
                    'orderby'      => 'menu_order',
                    'has_archives' => false,
                );

                \wc_create_attribute($args);
            }
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Attribute (Attr Code %s) %s',
                print_r($attribute, true),
                $e->getMessage()
            ));
            return;
        }
    }


    /**
     * @param Attribute $attribute
     */
    public function createOrUpdateAttribute($attrCode, $attrName, $attrType)
    {
        return $this->createOrUpdateFromArray([
            'code'   => $attrCode,
            'name'   => $attrName,
            'type'   => $attrType,
            'locale' => 'fr_FR',
        ]);
    }
}
