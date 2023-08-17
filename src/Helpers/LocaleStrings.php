<?php

namespace AmphiBee\AkeneoConnector\Helpers;

use Illuminate\Support\Collection;
use AmphiBee\AkeneoConnector\Helpers\Translator;

/**
 * This file is part of the Amphibee package.
 *
 * This class helps registrating translation strings.
 * This is used for translating items such as taxonomies.
 *
 * Use the Facade (AmphiBee\AkeneoConnector\Facade\LocaleStrings)
 * to statically call this singleton methods
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @version    1.1.2
 * @access     public
 */
class LocaleStrings
{
    /**
     * The option name to register strings in database
     */
    private const OPTION_NAME = 'akeneo_translation_strings';

    /**
     * The i18n group name to register strings
     */
    private const TRANSLATION_GROUP = 'Akeneo Connector';


    /**
     * The singleton instance.
     *
     * @var LocaleStrings
     */
    private static $_instance = null;

    /**
     * The strings to be registred in database.
     *
     * @var Collection
     */
    private $_store;

    /**
     * The corresponding translations
     *
     * @var Collection
     */
    private $_trans;

    /**
     * The translator instance
     *
     * @var Translator
     */
    private $translator;

    /**
     * Gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance()
    {
        if (static::$_instance === null) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * prevent the instance from instancied directly
     */
    private function __construct()
    {
        $this->_store = new Collection($this->retreive());
        $this->_trans = new Collection;

        $this->translator = new Translator;
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
    }


    /**
     * Add a named string to the store.
     *
     * @return void
     */
    public function register(string $name, string $string): void
    {
        $this->_store->put($name, $string);
    }


    /**
     * Add a translation for a given string and a given locale.
     *
     * @return void
     */
    public function translate(string $string, string $translation, string $locale): void
    {
        $this->_trans->push([
            'from' => $string,
            'to'   => $translation,
            'in'   => $locale,
        ]);
    }


    /**
     * Get registred translation strings.
     *
     * @return void
     */
    public function getAll(): Collection
    {
        return $this->_store->unique();
    }


    /**
     * Get registred translations.
     *
     * @return void
     */
    public function getAllTranslations(): Collection
    {
        return $this->_trans;
    }


    /**
     * Save translation strings in database
     *
     * @return bool
     */
    protected function retreive()
    {
        return get_option(static::OPTION_NAME, []);
    }


    /**
     * Get translation strings from database
     *
     * @return bool
     */
    protected function save()
    {
        return update_option(static::OPTION_NAME, $this->getAll()->toArray());
    }


    /**
     * Commit all stuff into database, at end of all imports
     */
    public function commit()
    {
        # Prevent running if no translation plugin is found
        if (!$this->translator->active()) {
            return;
        }

        # Save translation strings options
        $this->save();

        # Register strings via Polylang/WMPL. This actions must be be done each time..
        foreach ($this->_store as $name => $string) {
            $this->translator->registerString($string, $name, static::TRANSLATION_GROUP);
        }

        # If we have any translations, save then into Polylang/WPML
        if ($this->_trans->count()) {
            foreach ($this->_trans as $single) {
                $this->translator->setStringIn($single['from'], $single['to'], $single['in']);
            }
        }
    }
}
