<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service;

use Illuminate\Support\Arr;
use OP\Support\Facades\Config;

/**
 * Service pour gérer la configuration des tentatives d'appels API
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class RetryConfigService
{
    /**
     * Récupère le nombre maximum de tentatives
     *
     * @return int
     */
    public static function getMaxRetries(): int
    {
        return Config::get('setup.akeneo.retry.max_retries', 3);
    }

    /**
     * Récupère le délai entre les tentatives (en secondes)
     *
     * @return int
     */
    public static function getRetryDelay(): int
    {
        return Config::get('setup.akeneo.retry.delay', 2);
    }

    /**
     * Récupère les codes HTTP qui doivent déclencher une nouvelle tentative
     *
     * @return array
     */
    public static function getRetryStatusCodes(): array
    {
        return Config::get('setup.akeneo.retry.status_codes', [429, 500, 502, 503, 504]);
    }

    /**
     * Vérifie si les tentatives sont activées
     *
     * @return bool
     */
    public static function isRetryEnabled(): bool
    {
        return Config::get('setup.akeneo.retry.enabled', true);
    }
}
