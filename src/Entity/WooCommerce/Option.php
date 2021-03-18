<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Entity\WooCommerce;

class Option implements WooCommerceEntityInterface
{
    private string $code;
    private string $attribute;
    private array $labels;

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

    public function findOptionByAkeneoCode($attributeName) : int
    {
        $args = [
            'hide_empty'    => false,
            'fields'        => 'ids',
            'taxonomy'      => $attributeName,
            'meta_query'    => [
                'relation'  => 'AND',
                [
                    'key'   => '_akeneo_code',
                    'value' => $this->getCode(),
                ]
            ]
        ];

        $term_query = new \WP_Term_Query( $args );

        return is_array($term_query->terms) && count($term_query->terms) > 0 ? $term_query->terms[0] : 0;
    }
}
