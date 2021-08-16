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
use AmphiBee\AkeneoConnector\Helpers\InsertTerm;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Option as WP_Option;

/**
 * Persist options into wordpress.
 *
 * @support translations
 */
class OptionDataPersister extends AbstractDataPersister
{
    /**
     * Create or Update an existing option in the database.
     *
     * @support translations
     */
    public function createOrUpdate(WP_Option $option): void
    {
        try {
            $attribute = $option->getAttribute();
            $taxonomy  = $option->guessTaxonomyName();
            $mapping   = Settings::getMappingValue($attribute);
            $ids       = [];

            if ($mapping !== 'global_attribute' || !taxonomy_exists($taxonomy)) {
                return;
            }

            # Make sure to only loop on locales active on both WordPress & current option
            $option_locales    = array_keys($option->getLabels());
            $available_locales = $this->translator->mergeAvailables($option_locales);

            foreach ($available_locales as $locale) {
                # Save ids in array to sync them as translation of each others
                $slug       = $this->translator->localeToSlug($locale);
                $ids[$slug] = $this->updateSingleTerm($option, $locale, $taxonomy);
            }

            $ids = array_filter($ids);

            # Set terms as translation of each others
            if (count($ids) > 1) {
                $this->translator->syncTerms($ids);
            }

            # catch error
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Option (OptCode %s) %s',
                print_r($option, true),
                $e->getMessage()
            ));

            return;
        }
    }


    /**
     * @deprecated use createOrUpdate instead
     */
    public function createOrUpdateOption(WP_Option $option): void
    {
        $this->createOrUpdate($option);
    }


    /**
     * Save a single option by locale.
     *
     * @param WP_Option $option
     * @param string    $locale
     * @param string    $taxonomy  The linked taxonomy to register the option on (as term)
     *
     * @return int
     */
    private function updateSingleTerm(WP_Option $option, string $locale, string $taxonomy): int
    {
        $code   = $option->getCode();
        $labels = $option->getLabels();
        $label  = $labels[$locale];

        if (!$label) {
            LoggerService::log(Logger::ERROR, sprintf('Missing term label for option code `%s` and locale `%s`', $code, $locale));
            return 0;
        }

        # Check if the term exists for the given locale
        $term = $option->getTermByAkeneoCode($locale);

        # Create term if doesn't exists
        if (!$term) {
            # Use InsertTerm class to force create term even if translated name is the same as another
            $term = (new InsertTerm($label, $taxonomy))->addTerm();

            if (!is_array($term) || !isset($term['term_id'])) {
                LoggerService::log(Logger::ERROR, sprintf('Could not create term `%s` for locale `%s`.', $label, $locale));
                return 0;
            }

            $term = Term::findOrFail($term['term_id']);
        }

        # Save meta datas
        $term->meta()->saveMany([
            TermMeta::updateSingle('_akeneo_code', $code, $term->id),
            TermMeta::updateSingle('_akeneo_lang', $locale, $term->id),
        ]);

        # Update label
        $term->name = $label;
        $term->save();

        # Define the current term lang
        if ($this->translator->active()) {
            $this->translator->setTermLang($term->id, $locale);
        }

        # Additonal metas using wp filters
        $this->addAdditionalMetas($option, $locale, $term->id);

        # Actions
        do_action('ak/a/option/single/after_save', $term->id, $option, $locale);
        do_action("ak/a/option/single/after_save/option={$code}", $term->id, $option, $locale);

        return $term->id;
    }


    /**
     * Add additionnal metas to the term before going to the next one.
     *
     * @return void
     */
    private function addAdditionalMetas(WP_Option $option, string $locale, $term_id)
    {
        # Add custom metas using filters
        $additionnal_metas = (array) apply_filters("ak/f/options/import/additionnal_metas/option={$option->getCode()}", [], $option, $locale);

        if ($option->getReferenceData()) {
            $additionnal_metas = (array) apply_filters("ak/f/options/import/additionnal_metas/refdata={$option->getReferenceData()}", $additionnal_metas, $option, $locale);
        }

        foreach ($additionnal_metas as $meta_key => $meta_value) {
            if (is_string($meta_key) && $meta_value) {
                update_term_meta($term_id, $meta_key, $meta_value);
            }
        }
    }
}
