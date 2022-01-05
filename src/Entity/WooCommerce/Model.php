<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

class Model implements WooCommerceEntityInterface
{
    private string $code;
    private ?string $parent;
    private array $categories;
    private array $values;
    private array $association;
    private string $family;
    private string $familyVariant;

    /**
     * Category constructor.
     *
     * @param string $code
     */
    public function __construct(string $code)
    {
        $this->code = $code;
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
    public function getFamily(): string
    {
        return $this->family;
    }

    /**
     * @param string $family
     *
     * @return $this
     */
    public function setFamily(string $family): self
    {
        $this->family = $family;

        return $this;
    }

    /**
     * @return string
     */
    public function getFamilyVariant(): string
    {
        return $this->familyVariant;
    }

    /**
     * @param string $familyVariant
     *
     * @return $this
     */
    public function setFamilyVariant(string $familyVariant): self
    {
        $this->familyVariant = $familyVariant;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * @param string|null $parent
     *
     * @return $this
     */
    public function setParent(?string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return array
     */
    public function getAssociation(): array
    {
        return $this->association;
    }

    /**
     * @param array $association
     *
     * @return $this
     */
    public function setAssociation(array $association): self
    {
        $this->association = $association;

        return $this;
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     *
     * @return $this
     */
    public function setCategories(array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }
}
