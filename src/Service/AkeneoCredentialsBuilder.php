<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service;

use AmphiBee\AkeneoConnector\Admin\Settings;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Credentials;

class AkeneoCredentialsBuilder
{
    /**
     * Read settings and create a Credential instance.
     *
     * @return Credentials
     */
    public static function getCredentials(): Credentials
    {
        $settings = Settings::getAkeneoSettings()['credentials'] ?? [];

        $credentials = new Credentials(
            $settings['akaneo_host']            ?? '',
            $settings['akaneo_client_id']       ?? '',
            $settings['akaneo_client_secret']   ?? '',
            $settings['akaneo_user']            ?? '',
            $settings['akaneo_password']        ?? '',
            $settings['akaneo_channel']        ?? '',
        );

        return $credentials;
    }
}
