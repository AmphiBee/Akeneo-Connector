<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

class i18n
{
    public static function getLanguages() {
        return PLL()->model->get_languages_list();
    }
}
