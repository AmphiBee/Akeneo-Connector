<?php declare(strict_types=1);

namespace AmphiBee\AkeneoConnector\Helpers;

use OP\Framework\Models\Post;
use OP\Framework\Models\Term;

/**
 * This file is part of the Amphibee package.
 *
 * The fetcher helps us retreive WP posts/terms from Akeneo Codes and the corresponding locale.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @version    1.1.2
 * @access     public
 */
class Fetcher
{
    /**
     * Find the corresponding term based on akeneo code, taxonomy and locale.
     *
     * @param string $code     The Akeneo code
     * @param string $taxonomy The related taxonomy
     * @param string $locale   The locale to use
     */
    public static function getTermByAkeneoCode(string $code, string $taxonomy, string $locale): ?Term
    {
        return Term::whereTaxonomy($taxonomy)
                ->hasMeta('_akeneo_code', $code)
                ->hasMeta('_akeneo_lang', $locale)
                ->first();
    }


    /**
     * Find the corresponding term based on akeneo code, taxonomy and locale.
     *
     * @param string $code     The Akeneo code
     * @param string $taxonomy The related taxonomy
     * @param string $locale   The locale to use
     */
    public static function getTermBooleanByAkeneoCode(string $bool, string $taxonomy, string $locale): ?Term
    {
        return Term::whereTaxonomy($taxonomy)
                ->hasMeta('_akeneo_lang', $locale)
                ->hasMeta('_akeneo_opt_boolean', $bool)
                ->first();
    }


    /**
     * Find the corresponding product based on akeneo code and locale.
     *
     * @param string $code     The Akeneo code
     * @param string $locale   The locale to use
     */
    public static function getProductByAkeneoCode(string $code, string $locale): ?Post
    {
        return Post::type('product')
                ->hasMeta('_akeneo_code', $code)
                ->hasMeta('_akeneo_lang', $locale)
                ->first();
    }

    /**
     * Find the corresponding product variation based on akeneo code and locale.
     *
     * @param string $code     The Akeneo code
     * @param string $locale   The locale to use
     */
    public static function getProductVariationByAkeneoCode(string $code, string $locale): ?Post
    {
        return Post::where('post_type', '=', 'product_variation')
                ->hasMeta('_akeneo_code', $code)
                ->hasMeta('_akeneo_lang', $locale)
                ->first();
    }


    /**
     * Get product from SKU Code
     *
     * @param string  $sku  The SKU code
     *
     * @return Post|null
     */
    public static function getProductBySku(string $sku, string $locale): ?Post
    {
        return Post::where('post_type', '=', 'product')
                ->hasMeta('_sku', $sku)
                ->first();
    }


    /**
     * Find the corresponding term id based on akeneo code, taxonomy and locale.
     *
     * @param string $code     The Akeneo code
     * @param string $taxonomy The related taxonomy
     * @param string $locale   The locale to use
     */
    public static function getTermIdByAkeneoCode(string $code, string $taxonomy, string $locale): int
    {
        $term = static::getTermByAkeneoCode($code, $taxonomy, $locale);
        return $term ? $term->id : 0;
    }


    /**
     * Find the corresponding product id based on akeneo code and locale.
     *
     * @param string $code     The Akeneo code
     * @param string $locale   The locale to use
     */
    public static function getProductIdByAkeneoCode(string $code, string $locale): int
    {
        $product = static::getProductByAkeneoCode($code, $locale);
        return $product ? $product->id : 0;
    }


    /**
     * Get product ID from SKU
     * Check in the DB if a product has already a given SKU code
     *
     * @param   string  $sku  The SKU code
     * @return  int           The product id if found, 0 otherwise
     */
    public static function getProductIdBySku(string $sku, string $locale): int
    {
        $post = static::getProductBySku($sku, $locale);
        return $post ? $post->id : 0;
    }
}
