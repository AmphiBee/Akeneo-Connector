<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

class CustomReferenceData implements LocalizableEntityInterface
{
    private string $code;
    private string $type;
    private string $target;
    private bool $localizable;
    private array $labels;
    private array $metaDatas;

    /**
     * Attribute constructor.
     *
     * @param string $code
     * @param string $type
     * @param string $group
     * @param bool   $localizable
     * @param array  $labels
     * @param array  $groupLabels
     */
    public function __construct(
        string $code,
        string $type,
        array $labels,
        array $metaDatas,
        string $target
    ) {
        $this->code = $code;
        $this->type = $type;
        $this->labels = $labels;
        $this->metaDatas = $metaDatas;
        $this->target = $target;
        $this->localizable = false;
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
     * @return string
     */
    public function getMetaDatas(): array
    {
        return $this->metaDatas;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setMetaDatas(array $metaDatas): self
    {
        $this->metaDatas = $metaDatas;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $entity
     *
     * @return $this
     */
    public function setTarget(string $entity): self
    {
        $this->target = $entity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLocalizable(): bool
    {
        return $this->localizable;
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
