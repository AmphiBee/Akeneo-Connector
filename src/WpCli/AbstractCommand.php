<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use WP_CLI;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Helpers\Translator;
use AmphiBee\AkeneoConnector\Facade\LocaleStrings;
use AmphiBee\AkeneoConnector\Service\LoggerService;

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
abstract class AbstractCommand
{
    /**
     * @var string The command name. Should be unique. Get prefixed by 'akaneo', eg: 'akaneo attributes _method_'
     */
    public static string $name = '';


    /**
     * @var string The command description shown in help.
     */
    public static string $desc = '';


    /**
     * @var string The command long description shown in help.
     */
    public static string $long_desc = '';


    /**
     * The Language driver used for translations.
     *
     * @var Translator
     */
    protected $translator;


    /**
     * Bootup Command classes
     */
    public function __construct()
    {
        # Enable error displaying for debugging on local env or if WP_DEBUG is true
        if ((defined(WP_DEBUG) && WP_DEBUG) || (defined(WP_ENV) && WP_ENV === 'development')) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }

        $this->translator = new Translator;

        WP_CLI::warning(sprintf('Detected wordpress languages: [ "%s" ]', implode('", "', $this->translator->available)));
    }


    /**
     * Finish up command, commit the LocaleString translations
     */
    public function __destruct()
    {
        LocaleStrings::commit();
    }


    /**
     * Log into debug log file.
     *
     * @param string $message
     * @param string $type    Can be warning, success
     *
     * @return void
     */
    protected function log($message)
    {
        LoggerService::log(Logger::DEBUG, $message);
    }


    /**
     * Log into error log file.
     *
     * @param string $message
     * @param string $type    Can be warning, success
     *
     * @return void
     */
    protected function error($message)
    {
        LoggerService::log(Logger::ERROR, $message);
    }


    /**
     * Print into terminal and in debug log file.
     *
     * @param string $message
     * @param string $type    Can be warning, success
     *
     * @return void
     */
    protected function print($message, $type = 'warning')
    {
        WP_CLI::$type($message);
        LoggerService::log(Logger::DEBUG, $message);
    }


    /**
     * Get the command args options at register, such as shortdesc.
     *
     * @return array $args
     * @see https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-add-command/
     */
    public static function getCommandArgs()
    {
        return [
            'shortdesc' => static::$desc,
            'longdesc'  => static::$long_desc,
        ];
    }


    /**
     * Register the command into WP_Cli.
     *
     * @return void
     * @see https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-add-command/
     */
    public static function register()
    {
        WP_CLI::add_command(
            sprintf('akeneo %s', static::$name),
            static::class,
            static::getCommandArgs()
        );
    }
}
