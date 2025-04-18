<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractDataProvider
{
    protected Serializer $serializer;

    /**
     * AbstractEndpoint constructor.
     */
    public function __construct()
    {
        $this->serializer = $this->createSerializer();
    }

    /**
     * @return Serializer
     */
    private function createSerializer(): Serializer
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())];

        return new Serializer($normalizers, $encoders);
    }

    /**
     * @return Serializer
     */
    protected function getSerializer(): Serializer
    {
        return $this->serializer;
    }
}
