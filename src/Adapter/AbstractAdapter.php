<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\LocalizableEntityInterface;

abstract class AbstractAdapter
{
    /**
     * @param LocalizableEntityInterface $entity
     * @param string                     $locale
     *
     * @return string
     */
    public function getLocalizedLabel(LocalizableEntityInterface $entity, string $locale = 'fr_FR'): string
    {
        $labels = $entity->getLabels();

        return $labels[$locale] ?? '';
    }
}
