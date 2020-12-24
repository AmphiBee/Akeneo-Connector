<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service;

use Error;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerService
{
    private const LOG_PATH_DEBUG = ABSPATH . '../../logs/akeneo_connector-debug.log';
    private const LOG_PATH_ERROR = ABSPATH . '../../logs/akeneo_connector-error.log';

    private static ?LoggerInterface $logger = null;

    /**
     * @return LoggerInterface
     */
    public static function getLogger(): LoggerInterface
    {
        if (!self::$logger) {
            try {
                self::$logger = new Logger('akeneo_connector');
                self::$logger->pushHandler(new StreamHandler(self::LOG_PATH_DEBUG, Logger::DEBUG));
                self::$logger->pushHandler(new StreamHandler(self::LOG_PATH_ERROR, Logger::ERROR));
            } catch (Exception $e) {
                throw new Error('An error occurred with monolog, cannot instantiate stream');
            }
        }

        return self::$logger;
    }

    /**
     * @param int    $level
     * @param string $message
     * @param array  $context
     */
    public static function log(int $level, string $message, array $context = array()): void
    {
        self::getLogger()->log($level, $message, $context);
    }
}
