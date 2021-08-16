<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use Monolog\Logger;
use OP\Lib\WpEloquent\Model\Term;
use OP\Lib\WpEloquent\Model\Meta\TermMeta;
use AmphiBee\AkeneoConnector\Helpers\InsertTerm;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category as WP_Category;

class CategoryDataPersister extends AbstractDataPersister
{
    /**
     * Create or Update an existing product category in the database.
     *
     * @support translations
     */
    public function createOrUpdate(WP_Category $category): void
    {
        try {
            $ids = [];

            # Make sure to only loop on locales active on both WordPress & current category
            $category_locales  = array_keys($category->getLabels());
            $available_locales = $this->translator->mergeAvailables($category_locales);

            foreach ($available_locales as $locale) {
                # Save ids in array to sync them as translation of each others
                $slug       = $this->translator->localeToSlug($locale);
                $ids[$slug] = $this->updateSingleTerm($category, $locale);
            }

            $ids = array_filter($ids);

            # Set terms as translation of each others
            if (count($ids) > 1) {
                $this->translator->syncTerms($ids);
            }

            # catch error
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize category (Category Code %s) %s',
                print_r($category, true),
                $e->getMessage()
            ));

            return;
        }
    }


    /**
     * Save a single category by locale.
     *
     * @param WP_Category  $category
     * @param string       $locale
     *
     * @return int
     */
    private function updateSingleTerm(WP_Category $category, string $locale): int
    {
        $name   = $category->getName();
        $labels = $category->getLabels();
        $parent = $category->getParent();
        $label  = $labels[$locale];

        if (!$label) {
            LoggerService::log(Logger::ERROR, sprintf('Missing term label for option code `%s` and locale `%s`', $name, $locale));
            return 0;
        }

        # Check if the term exists for the given locale
        $term = $category->getTermByAkeneoCode($locale);

        # Create term if doesn't exists
        if (!$term) {
            # Use InsertTerm class to force create term even if translated name is the same as another
            $term = (new InsertTerm($label, 'product_cat'))->addTerm();

            if (!is_array($term) || !isset($term['term_id'])) {
                LoggerService::log(Logger::ERROR, sprintf('Could not create term `%s` for locale `%s`.', $label, $locale));
                return 0;
            }

            $term = Term::findOrFail($term['term_id']);
        }

        # Update label
        $term->name = $label;
        $term->save();

        # Assign parent
        if ($parent) {
            $parent = (new WP_Category($parent))->getTermByAkeneoCode($locale);

            # Update termtax
            $term->taxonomy->parent = $parent ? $parent->id : 0;
            $term->taxonomy->save();
        }

        # Save meta datas
        $term->meta()->saveMany([
            TermMeta::updateSingle('_akeneo_code', $name, $term->id),
            TermMeta::updateSingle('_akeneo_lang', $locale, $term->id),
        ]);

        # Define the current term lang
        if ($this->translator->active()) {
            $this->translator->setTermLang($term->id, $locale);
        }

        # Additonal metas using wp filters
        $this->addAdditionalMetas($category, $locale, $term->id);

        # Actions
        do_action('ak/a/category/single/after_save', $term->id, $category, $locale);
        do_action("ak/a/category/single/after_save/category={$name}", $term->id, $category, $locale);

        return $term->id;
    }


    /**
     * Add additionnal metas to the term before going to the next one.
     *
     * @return void
     */
    private function addAdditionalMetas(WP_Category $catgory, string $locale, $term_id)
    {
        # Add custom metas using filters
        $additionnal_metas = (array) apply_filters("ak/f/categories/import/additionnal_metas/catgory={$catgory->getName()}", [], $catgory, $locale);

        foreach ($additionnal_metas as $meta_key => $meta_value) {
            if (is_string($meta_key) && $meta_value) {
                update_term_meta($term_id, $meta_key, $meta_value);
            }
        }
    }
}
