<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

class Attribute implements WooCommerceEntityInterface
{
    private string $code;
    private string $name;
    private string $type;
    private string $group;
    private bool $localizable;
    private array $labels = [];
    private array $groupLabels = [];
    private array $metaDatas = [];
    /**
     * @var string|null
     */
    private ?string $hash = null;

    public function __construct(string $code = '', string $name = '', string $type = '')
    {
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->group = '';
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

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

    /**
     * @return array
     */
    public function getMetaDatas(): array
    {
        return $this->metaDatas;
    }

    /**
     * @param array $metaDatas
     *
     * @return $this
     */
    public function setMetaDatas(array $metaDatas): self
    {
        $this->metaDatas = $metaDatas;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return self
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }
}
