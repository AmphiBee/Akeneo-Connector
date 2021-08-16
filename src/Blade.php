<?php

namespace AmphiBee\AkeneoConnector;

use Jenssegers\Blade\Blade as BaseBlade;

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
final class Blade extends BaseBlade
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
        $views_path = __DIR__ . '/../resources/views';
        $cache_path = __DIR__ . '/../cache/blade';

        if (!is_dir($cache_path)) {
            mkdir($cache_path, 0770, true);
        }

        parent::__construct($views_path, $cache_path);
    }

    public static function template(string $view, array $data = [], array $mergeData = []): string
    {
        return self::getInstance()->render($view, $data, $mergeData);
    }

    public static function print(string $view, array $data = [], array $mergeData = []): void
    {
        echo self::getInstance()->render($view, $data, $mergeData);
    }
}
