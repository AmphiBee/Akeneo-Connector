<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Helpers;

use InvalidArgumentException;
use WP_Error;

/**
 * This file is part of the Amphibee package.
 *
 * This class is used to insert terms into the database :
 * If term names are identical, instead of throwing an error like
 * wp_insert_term(), the function will add a valid term
 *
 * ℹ️ This is usefull for translated terms having the same name shared across languages.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Pieter Goosen
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @version    1.1
 * @access     public
 * @see        https://wordpress.stackexchange.com/questions/211581/wp-insert-term-auto-unique-name
 */
class InsertTerm
{
    /**
     * Arguments to hold defaults
     * @since 1.0.0
     * @var array
    */
    protected $defaults = [
        'alias_of' => '',
        'description' => '',
        'parent' => 0,
        'slug' => ''
    ];

    /**
     * Arguments set by user
     * @since 1.0.0
     * @var array
    */
    protected $args = [];

    /**
     * Term to insert
     * @since 1.0.0
     * @var string
    */
    protected $term = null;

    /**
     * Taxonomy the term should belong to
     * @since 1.0.0
     * @var string
    */
    protected $taxonomy = null;

    /**
     * Constructor
     *
     * @param string $term = null
     * @param string $taxonomy = null
     * @param array  $args = []
     * @since 1.0.0
     */
    public function __construct($term = null, $taxonomy = null, $args = [])
    {
        $this->term     = $term;
        $this->taxonomy = $taxonomy;
        if (is_array($args) && !empty($args)) {
            $this->args = array_merge($this->defaults, $args);
        } else {
            $this->args = $this->defaults;
        }
    }

    /**
     * Public method wpdb()
     *
     * Returns the global wpdb class
     *
     * @since 1.0.0
     * @return $wpdb
     */
    public function wpdb()
    {
        global $wpdb;

        return $wpdb;
    }

    /**
     * Private method validateVersion()
     *
     * Validate the current WordPress version
     *
     * @since 1.0.0
     * @return $validateVersion
     */
    private function validateVersion()
    {
        global $wp_version;

        $validateVersion = false;

        if ('4.4' > $wp_version) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Your WordpPress version is too old. A minimum version of WordPress 4.4 is expected. Please upgrade'),
                    __METHOD__
                )
            );
        }

        return $validateVersion = true;
    }

    /**
     * Private method validateTaxonomy()
     *
     * Validate the $taxonomy value
     *
     * @since 1.0.0
     * @return $validateTaxonomy
     */
    private function validateTaxonomy()
    {
        $validateTaxonomy = filter_var($this->taxonomy, FILTER_SANITIZE_STRING);
        // Check if taxonomy is valid
        if (!taxonomy_exists($validateTaxonomy)) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Your taxonomy does not exists, please add a valid taxonomy'),
                    __METHOD__
                )
            );
        }

        return $validateTaxonomy;
    }

    /**
     * Private method validateTerm()
     *
     * Validate the $term value
     *
     * @since 1.0.0
     * @return $validateTerm
     */
    private function validateTerm()
    {
        /**
         * Filter a term before it is sanitized and inserted into the database.
         *
         * @since 1.0.0
         *
         * @param string $term     The term to add or update.
         * @param string $taxonomy Taxonomy slug.
         */
        $validateTerm = apply_filters('pre_insert_term', $this->term, $this->validateTaxonomy());

        // Check if the term is not empty
        if (empty($validateTerm)) {
            throw new InvalidArgumentException(
                sprintf(
                    __('$term should not be empty, please add a valid value'),
                    __METHOD__
                )
            );
        }

        // Check if term is a valid integer if integer is passed
        if (is_int($validateTerm)
             && 0 == $validateTerm
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Invalid term id supplied, please asdd a valid value'),
                    __METHOD__
                )
            );
        }

        // Term is not empty, sanitize the term and trim any white spaces
        $validateTerm = htmlspecialchars(trim($validateTerm));
        if (empty($validateTerm)) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Invalid term supplied, please add a valid term name'),
                    __METHOD__
                )
            );
        }

        return $validateTerm;
    }

    /**
     * Private method parentExist()
     *
     * Validate if the parent term exist if passed
     *
     * @since 1.0.0
     * @return $parentexist
     */
    private function parentExist()
    {
        $parentExist = $this->args['parent'];

        if ($parentExist > 0
             && !term_exists((int) $parentExist)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Invalid parent ID supplied, no term exists with parent ID passed. Please add a valid parent ID'),
                    __METHOD__
                )
            );
        }

        return $parentExist;
    }

    /**
     * Private method sanitizeTerm()
     *
     * Sanitize the term to insert
     *
     * @since 1.0.0
     * @return $sanitizeTerm
     */
    private function sanitizeTerm()
    {
        $taxonomy              = $this->validateTaxonomy();
        $arguments             = $this->args;

        $arguments['taxonomy'] = $taxonomy;
        $arguments['name']     = $this->validateTerm();
        $arguments['parent']   = $this->parentExist();

        // Santize the term
        $arguments = sanitize_term($arguments, $taxonomy, 'db');

        // Unslash name and description fields and cast parent to integer
        $arguments['name']        = wp_unslash($arguments['name']);
        $arguments['description'] = wp_unslash($arguments['description']);
        $arguments['parent']      = (int) $arguments['parent'];

        return (object) $arguments;
    }

    /**
     * Private method slug()
     *
     * Get or create a slug if no slug is set
     *
     * @since 1.0.0
     * @return $slug
     */
    private function slug()
    {
        $term = $this->sanitizeTerm();
        $new_slug = $term->slug;
        if (!$new_slug) {
            $slug = sanitize_title($term->name);
        } else {
            $slug = $new_slug;
        }

        return $slug;
    }

    /**
     * Public method addTerm()
     *
     * Add the term to db
     *
     * @since 1.0.0
     */
    public function addTerm()
    {
        $wpdb        = $this->wpdb();
        $term        = $this->sanitizeTerm();
        $taxonomy    = $term->taxonomy;
        $name        = $term->name;
        $parent      = $term->parent;
        $term_group  = $term->term_group ?? 0;
        $description = '';

        $term_group = 0;

        if ($term->alias_of) {
            $alias = get_term_by(
                'slug',
                $term->alias_of,
                $term->taxonomy
            );
            if (!empty($alias->term_group)) {
                // The alias we want is already in a group, so let's use that one.
                $term_group = $alias->term_group;
            } elseif (! empty($alias->term_id)) {
                /*
                 * The alias is not in a group, so we create a new one
                 * and add the alias to it.
                 */
                $term_group = $wpdb->get_var(
                    "SELECT MAX(term_group)
                    FROM $wpdb->terms"
                ) + 1;

                wp_update_term(
                    $alias->term_id,
                    $this->args['taxonomy'],
                    [
                        'term_group' => $term_group,
                    ]
                );
            }
        }

        $slug = wp_unique_term_slug(
            $this->slug(),
            $term
        );

        if (false === $wpdb->insert($wpdb->terms, compact('name', 'slug', 'term_group'))) {
            return new WP_Error('db_insert_error', __('Could not insert term into the database'), $wpdb->last_error);
        }

        $term_id = (int) $wpdb->insert_id;

        // Seems unreachable, However, Is used in the case that a term name is provided, which sanitizes to an empty string.
        if (empty($slug)) {
            $slug = sanitize_title(
                $slug,
                $term_id
            );

            /** This action is documented in wp-includes/taxonomy.php */
            do_action('edit_terms', $term_id, $taxonomy);
            $wpdb->update($wpdb->terms, compact('slug'), compact('term_id'));

            /** This action is documented in wp-includes/taxonomy.php */
            do_action('edited_terms', $term_id, $taxonomy);
        }

        $tt_id = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT tt.term_taxonomy_id
                FROM $wpdb->term_taxonomy AS tt
                INNER JOIN $wpdb->terms AS t
                ON tt.term_id = t.term_id
                WHERE tt.taxonomy = %s
                AND t.term_id = %d
            ",
                $taxonomy,
                $term_id
            )
        );

        if (!empty($tt_id)) {
            return [
                'term_id'          => $term_id,
                'term_taxonomy_id' => $tt_id
            ];
        }

        $wpdb->insert(
            $wpdb->term_taxonomy,
            compact('term_id', 'taxonomy', 'description', 'parent') + ['count' => 0]
        );
        $tt_id = (int) $wpdb->insert_id;


        /**
         * Fires immediately after a new term is created, before the term cache is cleaned.
         *
         * @since 2.3.0
         *
         * @param int    $term_id  Term ID.
         * @param int    $tt_id    Term taxonomy ID.
         * @param string $taxonomy Taxonomy slug.
         */
        do_action("create_term", $term_id, $tt_id, $taxonomy);

        /**
         * Fires after a new term is created for a specific taxonomy.
         *
         * The dynamic portion of the hook name, `$taxonomy`, refers
         * to the slug of the taxonomy the term was created for.
         *
         * @since 2.3.0
         *
         * @param int $term_id Term ID.
         * @param int $tt_id   Term taxonomy ID.
         */
        do_action("create_$taxonomy", $term_id, $tt_id);

        /**
         * Filter the term ID after a new term is created.
         *
         * @since 2.3.0
         *
         * @param int $term_id Term ID.
         * @param int $tt_id   Taxonomy term ID.
         */
        $term_id = apply_filters('term_id_filter', $term_id, $tt_id);

        clean_term_cache($term_id, $taxonomy);

        /**
         * Fires after a new term is created, and after the term cache has been cleaned.
         *
         * @since 2.3.0
         *
         * @param int    $term_id  Term ID.
         * @param int    $tt_id    Term taxonomy ID.
         * @param string $taxonomy Taxonomy slug.
         */
        do_action('created_term', $term_id, $tt_id, $taxonomy);

        /**
         * Fires after a new term in a specific taxonomy is created, and after the term
         * cache has been cleaned.
         *
         * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
         *
         * @since 2.3.0
         *
         * @param int $term_id Term ID.
         * @param int $tt_id   Term taxonomy ID.
         */
        do_action("created_$taxonomy", $term_id, $tt_id);

        return [
            'term_id'          => $term_id,
            'term_taxonomy_id' => $tt_id
        ];
    }
}
