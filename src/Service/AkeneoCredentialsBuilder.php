<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Credentials;

class AkeneoCredentialsBuilder
{
    public static function getCredentials(): Credentials
    {
        return new Credentials(
            'https://akeneo.meo.fr',
            '1_3bgvsldt25a8wo0kgok4wwk400c4kc0ok8oso4gkgw8cg8w4kw',
            '16ifma4tgaw0g88wkswo0ow4448cw8w84kgk4oskck480kwoc4',
            'woocommerce_0731',
            '7b5dee1fe'
        );
    }
}
