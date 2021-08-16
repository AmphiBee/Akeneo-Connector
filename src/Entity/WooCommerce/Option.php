<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

use OP\Lib\WpEloquent\Model\Term;
use AmphiBee\AkeneoConnector\Helpers\Fetcher;

class Option implements WooCommerceEntityInterface
{
    private string $code;
    private string $attribute;
    private array $labels;
    private array $meta_datas;
    private string $reference_data;

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->reference_data = '';
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
    public function getAttribute(): string
    {
        return $this->attribute;
    }

    /**
     * @param string $attribute
     *
     * @return $this
     */
    public function setAttribute(string $attribute): self
    {
        $this->attribute = $attribute;

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
    public function getReferenceData(): string
    {
        return $this->reference_data;
    }

    /**
     * @param array $reference_data
     *
     * @return $this
     */
    public function setReferenceData(string $reference_data): self
    {
        $this->reference_data = $reference_data;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetaDatas(): array
    {
        return $this->meta_datas;
    }

    /**
     * @param array $meta_datas
     *
     * @return $this
     */
    public function setMetaDatas(array $meta_datas): self
    {
        $this->meta_datas = $meta_datas;

        return $this;
    }


    /**
     * Try to guess the linked taxonomy to look in, based on Attribute
     *
     * @return string
     */
    public function guessTaxonomyName(): string
    {
        return sprintf('pa_%s', strtolower($this->getAttribute()));
    }


    /**
     * Search the corresponding term id based on akeneo code.
     *
     * @param string $locale    The locale to use
     * @param string $taxonomy  The taxonomy to look in. If null, we're guessing based on Attribute name
     *
     */
    public function getTermByAkeneoCode(string $locale, ?string $taxonomy = null): ?Term
    {
        $taxonomy = $taxonomy ?: $this->guessTaxonomyName();

        return Fetcher::getTermByAkeneoCode($this->getCode(), $taxonomy, $locale);
    }
}
