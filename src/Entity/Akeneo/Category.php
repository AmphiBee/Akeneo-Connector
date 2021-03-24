<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\Akeneo;

class Category implements LocalizableEntityInterface
{
    private string $code;
    private ?string $parent;
    private array $labels;
    private ?string $description;
    private ?string $descriptionEN;
    private ?string $categoryContentText;
    private ?string $categoryContentTextEN;
    private ?string $miniature;
    private ?string $categoryContentImage;

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
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
     * @param ?string $parent
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
     * @return string|null
     */
    public function getDescriptionEN(): ?string
    {
        return $this->descriptionEN;
    }

    /**
     * @param ?string $descriptionEN
     * @return $this
     */
    public function setDescriptionEN(?string $descriptionEN): self
    {
        $this->descriptionEN = $descriptionEN;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategoryContentText(): ?string
    {
        return $this->categoryContentText;
    }

    /**
     * @param string|null $categoryContentText
     * @return $this
     */
    public function setCategoryContentText(?string $categoryContentText): self
    {
        $this->categoryContentText = $categoryContentText;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategoryContentTextEN(): ?string
    {
        return $this->categoryContentTextEN;
    }

    /**
     * @param string|null $categoryContentTextEN
     * @return $this
     */
    public function setCategoryContentTextEN(?string $categoryContentTextEN): self
    {
        $this->categoryContentTextEN = $categoryContentTextEN;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMiniature(): ?string
    {
        return $this->miniature;
    }

    /**
     * @param string|null $miniature
     * @return $this
     */
    public function setMiniature(?string $miniature): self
    {
        $this->miniature = $miniature;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategoryContentImage(): ?string
    {
        return $this->categoryContentImage;
    }

    /**
     * @param string|null $categoryContentImage
     * @return $this
     */
    public function setCategoryContentImage(?string $categoryContentImage): self
    {
        $this->categoryContentImage = $categoryContentImage;

        return $this;
    }
}
