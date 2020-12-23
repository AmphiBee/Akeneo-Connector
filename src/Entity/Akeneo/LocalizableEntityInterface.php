<?php declare(strict_types=1);
/**
 * This file is part of the Adexos package.
 * (c) Adexos <contact@adexos.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

interface LocalizableEntityInterface
{
    public function getLabels(): array;
}
