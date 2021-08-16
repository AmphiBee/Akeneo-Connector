<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

class ReferenceEntity implements LocalizableEntityInterface
{
    private string $code;
    private string $type;
    private string $group;
    private bool $localizable;
    private array $labels;
    private array $groupLabels;

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
        bool $localizable,
        string $group,
        array $labels,
        array $groupLabels
    ) {
        $this->code = $code;
        $this->type = $type;
        $this->group = $group;
        $this->localizable = $localizable;
        $this->labels = $labels;
        $this->groupLabels = $groupLabels;
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
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     *
     * @return $this
     */
    public function setGroup(string $group): self
    {
        $this->group = $group;

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
     * @param bool $localizable
     *
     * @return $this
     */
    public function setLocalizable(bool $localizable): self
    {
        $this->localizable = $localizable;

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

    /**
     * @return array
     */
    public function getGroupLabels(): array
    {
        return $this->groupLabels;
    }

    /**
     * @param array $groupLabels
     *
     * @return $this
     */
    public function setGroupLabels(array $groupLabels): self
    {
        $this->groupLabels = $groupLabels;

        return $this;
    }
}
