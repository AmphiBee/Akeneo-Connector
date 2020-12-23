<?php declare(strict_types=1);
/**
 * This file is part of the Adexos package.
 * (c) Adexos <contact@adexos.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

class Option implements LocalizableEntityInterface
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
