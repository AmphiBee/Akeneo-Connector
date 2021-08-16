<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

class TagDataPersister extends TaxonomyDataPersister
{
    /**
     * Construct the taxonomy data persister.
     *
     * @param string $taxonomy The Wordpress taxonomy.
     */
    public function __construct()
    {
        parent::__construct('product_tag');
    }
}
