<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

class Variant extends Attribute
{
    private string $code;
    private array $labels;

    /**
     * Attribute constructor.
     *
     * @param string $code
     * @param array  $labels
     */
    public function __construct(string $code, array $labels)
    {
        $this->code = $code;
        $this->labels = $labels;

        parent::__construct($code, $type, $localizable, $group, $labels, $groupLabels, $target, $metaDatas);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @param array $labels
     *
     * @return $this
     */
    public function setLabels(array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }
}
