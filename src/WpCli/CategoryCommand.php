<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\CategoryAdapter;
use AmphiBee\AkeneoConnector\DataPersister\CategoryDataPersister;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class CategoryCommand extends AbstractCommand
{
    public static string $name = 'categories';

    public static string $desc = 'Supports Akaneo Categories import';

    public static string $long_desc = 'Import the Akeneo categories as WooCommerce categories (by default)';


    /**
     * Run the import command.
     */
    public function import(): void
    {
        # Debug
        $this->print('Starting categories import');

        $provider  = AkeneoClientBuilder::create()->getCategoryProvider();
        $adapter   = new CategoryAdapter();
        $persister = new CategoryDataPersister();

        do_action('ak/a/categories/before_import', $provider->getAll());

        $categories = (array) apply_filters('ak/f/categories/import_data', iterator_to_array($provider->getAll()));

        # Remove default categories (corresponding to site name) and clean $parent data
        $categories = $this->filterCategories($categories);

        # Iterate on categories and import them
        foreach ($categories as $ak_category) {
            $this->print(sprintf('Running Category with code: %s', $ak_category->getCode()));

            $wp_category = $adapter->fromCategory($ak_category);
            $persister->createOrUpdate($wp_category);
        }

        do_action('ak/a/categories/after_import', $provider->getAll());
    }


    /**
     * Exclude root categories from import array.
     * Remove the category parent if it's a root category.
     * Sort the array to get categories without parents first.
     *
     * @return array filtered categories
     */
    private function filterCategories(array $categories)
    {
        # Get category code of items without parent : they are the root categories to exclude
        $exclude = collect($categories)->map(function ($item) {
            return is_null($item->getParent()) ? $item->getCode() : null;
        })->filter()->toArray();

        $exclude[] = 'root_category';

        # Filter & sort the array
        $filtered = collect($categories)->map(function ($item) use ($exclude) {
            $code   = $item->getCode();
            $parent = $item->getParent();

            # Remove if item is excluded
            if (in_array($code, $exclude)) {
                return null;
            }
            # If parent unallowed, set parent = null
            if (in_array($parent, $exclude)) {
                $item->setParent(null);
            }

            return $item;
        })->filter()->sort(function ($a, $b) {
            # Sort catgories without parents at the beginning
            if ((bool) $a->getParent() === (bool) $b->getParent()) {
                return 0;
            }
            return is_null($a->getParent()) ? -1 : 1;
        })->toArray();

        return (array) apply_filters('ak/f/categories/import_data_filtered', $filtered, $categories, $exclude);
    }
}
