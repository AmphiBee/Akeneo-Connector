<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use Symfony\Component\Serializer\Serializer;
use AmphiBee\AkeneoConnector\Helpers\Translator;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

abstract class AbstractDataPersister
{
    protected Serializer $serializer;
    protected Translator $translator;

    /**
     * AbstractEndpoint constructor.
     */
    public function __construct()
    {
        $this->serializer = $this->createSerializer();
        $this->translator = new Translator;
    }

    /**
     * @return Serializer
     */
    private function createSerializer(): Serializer
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

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
