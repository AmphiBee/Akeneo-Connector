<?php declare(strict_types=1);

namespace AmphiBee\AkeneoConnector\Helpers;

use OP\Support\Facades\ObjectPress;
use OP\Framework\Contracts\LanguageDriver;

/**
 * This file is part of the Amphibee package.
 *
 * This class helps translating Akeneo stuffs using Polylang or WMPL (/!\ WPML untested).
 * It makes us of ObjectPress LanguageDriver
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @version    1.1.2
 * @access     public
 */
class Translator
{
    /**
     * Determine if a translation plugin is active.
     *
     * @var bool
     */
    protected bool $active;


    /**
     * The language driver.
     *
     * @var LanguageDriver
     */
    protected ?LanguageDriver $driver;


    /**
     * Available languages.
     *
     * @var array
     */
    public array $available;


    /**
     * Default language.
     *
     * @var string
     */
    public string $default;


    /**
     * Class initiator. Uses ObjectPress to translate using translation plugins.
     * Available plugins : Polylang, WPML
     */
    public function __construct()
    {
        # Check if a provider has been registred based on active plugins
        $this->active = ObjectPress::app()->bound(LanguageDriver::class);

        # If a driver is found, save it for dynamic calls
        $this->driver = $this->active ? ObjectPress::app()->make(LanguageDriver::class) : null;

        # Set defaults
        $this->boot();
    }


    /**
     * Bootup the Translator instance
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->active) {
            $this->default   = get_locale();
            $this->available = [get_locale()];

            return;
        }

        $this->default = $this->driver->getPrimaryLang('locale');

        $this->available = collect($this->driver->getAvailableLanguages())->map(function ($l) {
            return $l->locale;
        })->toArray();
    }


    /**
     * Determine if a translation plugin is active.
     *
     * @return bool
     */
    public function active()
    {
        return $this->active;
    }


    /**
     * Returns the Translator driver.
     *
     * @return Translator|null
     */
    public function driver()
    {
        return $this->driver;
    }


    /**
     * Returns the current used language.
     *
     * @return string
     */
    public function current()
    {
        if (!$this->active()) {
            return $this->default;
        }

        return $this->driver->getCurrentLang('locale');
    }


    /**
     * Make sure to return locales available on both WordPress & the given locale source.
     * Always returns the default locale at first position if given in $locales param.
     *
     * @param $locales The source locales available, to be intersect with WP availables.
     *
     * @return array
     */
    public function mergeAvailables(array $locales)
    {
        $available = array_intersect($locales, $this->available);

        if (!in_array($this->default, $available)) {
            return $available;
        }

        # Make sure to start the array with default language
        return [$this->default] + array_diff($available, [$this->default]);
    }


    /**
     * Transfom given locale into slug.
     *
     * @return string
     */
    public function localeToSlug(string $locale): string
    {
        $matches = [];

        if (preg_match('/^[a-z]{2}$/', $locale)) {
            return $locale;
        }
        if (preg_match('/^([a-z]{2})_[A-Z]{2}$/', $locale, $matches)) {
            return $matches[1];
        }

        return '';
    }


    /**
     * Handle dynamic, calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!$this->driver) {
            return null;
        }

        switch (count($args)) {
            case 0:
                return $this->driver->$method();

            case 1:
                return $this->driver->$method($args[0]);

            case 2:
                return $this->driver->$method($args[0], $args[1]);

            case 3:
                return $this->driver->$method($args[0], $args[1], $args[2]);

            case 4:
                return $this->driver->$method($args[0], $args[1], $args[2], $args[3]);

            default:
                return call_user_func_array([$this, $method], $args);
        }
    }
}
