<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use OP\Lib\WpEloquent\Model\Post;
use OP\Lib\WpEloquent\Model\Term;

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
class MigrateTranslationsCommand extends AbstractCommand
{
    public static string $name = 'migrate_v1-1';

    public static string $desc = 'Helps you migrate from v1.0.6 (without translation) to v1.1 (with translations)';

    public static string $long_desc = 'Run this to avoid duplicated terms/posts !';

    public static string $akeneo_default_lang = 'fr_FR';

    /**
     * Run the import command.
     */
    public function run(): void
    {
        # Debug
        $this->print('Start migration !');

        $locale = static::$akeneo_default_lang;

        $post_count = 0;
        $term_count = 0;

        $posts = Post::type('product')
                    ->hasMeta('_akeneo_code')
                    ->get();

        $terms = Term::hasMeta('_akeneo_code')
                    ->get();


        foreach ($posts as $post) {
            if (! $post->meta->_akeneo_lang) {
                $post_count++;
                $post->saveMeta('_akeneo_lang', $locale);
            }
        }

        foreach ($terms as $term) {
            if (! $term->meta->_akeneo_lang) {
                $term_count++;
                $term->saveMeta('_akeneo_lang', $locale);
            }
        }

        $this->print(sprintf('Configured [ %s / %s ] posts with locale [ %s ].', $post_count, $posts->count(), $locale));
        $this->print(sprintf('Configured [ %s / %s ] terms with locale [ %s ].', $term_count, $terms->count(), $locale));
    }
}
