<?php declare(strict_types=1);

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Helpers\Translator;
use AmphiBee\AkeneoConnector\Entity\Akeneo\LocalizableEntityInterface;

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
abstract class AbstractAdapter
{
    /**
     * The Language driver used for translations.
     *
     * @var Translator
     */
    protected $translator;


    /**
     * The current locale to use.
     *
     * @var string
     */
    protected $locale;


    /**
     * @param LocalizableEntityInterface $entity
     * @param string                     $locale
     *
     * @return string
     */
    public function getLocalizedLabel(LocalizableEntityInterface $entity, string $locale = 'fr_FR'): string
    {
        $labels = collect($entity->getLabels());

        return $labels->has($locale) ? $labels->get($locale) : $labels->first();
    }

    /**
     * Bootup adapter classes
     */
    public function __construct()
    {
        $this->translator = new Translator;
    }


    /**
     * Get the current locale used
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the locale to be used
     *
     * @return self
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
