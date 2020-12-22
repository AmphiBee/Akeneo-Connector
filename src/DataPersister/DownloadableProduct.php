<?php

namespace AmphiBee\AkeneoConnector\DataPersister;

class DownloadableProduct
{
    /**
     * Set downloadable product settings
     *
     * @param object $product | Product object
     * @param array $args | Product arguments
     */
    public static function setDownloadableSettings(object &$product, array $args)
    {
        // Downloadable (boolean)
        $product->set_downloadable(isset($args['downloadable']) ? $args['downloadable'] : false);

        if (isset($args['downloadable']) && $args['downloadable']) {
            $product->set_downloads(isset($args['downloads']) ? $args['downloads'] : []);
            $product->set_download_limit(isset($args['download_limit']) ? $args['download_limit'] : '-1');
            $product->set_download_expiry(isset($args['download_expiry']) ? $args['download_expiry'] : '-1');
        }
    }
}
