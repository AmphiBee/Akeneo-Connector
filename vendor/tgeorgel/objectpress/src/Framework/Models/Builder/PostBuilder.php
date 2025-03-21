<?php

namespace OP\Framework\Models\Builder;

use AmphiBee\Eloquent\Connection;
use OP\Support\Facades\ObjectPress;
use OP\Framework\Contracts\LanguageDriver;
use OP\Support\Language\Drivers\WPMLDriver;
use OP\Support\Language\Drivers\PolylangDriver;
use AmphiBee\Eloquent\Model\Builder\PostBuilder as BasePostBuilder;

/**
 * The post model query builder.
 *
 * @package  ObjectPress
 * @author   tgeorgel <thomas@hydrat.agency>
 * @access   public
 * @since    2.1
 */
class PostBuilder extends BasePostBuilder
{
    /**
     * Filter query by language.
     *
     * @param string $lang The requested lang. Can be 'current', 'default', or lang code (eg: 'en', 'fr', 'it'..)
     *
     * @return PostBuilder
     */
    public function lang(string $lang = 'current')
    {
        $app    = ObjectPress::app();
        $db     = Connection::instance();
        $prefix = $db->getPdo()->prefix();

        # No supported lang plugin detected
        if (!$app->bound(LanguageDriver::class)) {
            return $this;
        }

        $driver = $app->make(LanguageDriver::class);

        # Get the current lang slug
        if ($lang == 'current') {
            $lang = $driver->getCurrentLang();
        }
        
        # Get the default/primary lang slug
        if ($lang == 'default') {
            $lang = $driver->getPrimaryLang();
        }

        # WPML Support
        if (is_a($driver, WPMLDriver::class)) {
            return $this->whereExists(function ($query) use ($db, $prefix, $lang) {
                $table = $prefix . 'icl_translations';
    
                $query->select($db->raw(1))
                      ->from($table)
                      ->whereRaw("{$table}.element_id = {$prefix}posts.ID")
                      ->whereRaw("{$table}.element_type LIKE 'post_%'")
                      ->whereRaw("{$table}.language_code = '{$lang}'");
            });
        }
        
        # Polylang Support
        if (is_a($driver, PolylangDriver::class)) {
            return $this->whereHas(
                'taxonomies',
                fn ($tx) => $tx->where('taxonomy', 'language')
                               ->whereHas('term', fn ($q) => $q->where('slug', $lang))
            );
        }
    }
}
