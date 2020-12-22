<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use AmphiBee\AkeneoConnector\DataProvider\CategoryDataProvider;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Credentials;

class AkeneoClientBuilder
{
    private ?AkeneoPimClientInterface $client = null;

    private Credentials $credentials;
    private CategoryDataProvider $category;

    /**
     * AkeneoClientBuilder constructor.
     */
    public function __construct()
    {
        $this->credentials = AkeneoCredentialsBuilder::getCredentials();
        $this->category = new CategoryDataProvider($this->getClient());
    }

    /**
     * @return AkeneoClientBuilder
     */
    public static function create(): AkeneoClientBuilder
    {
        return new static();
    }

    /**
     * @return AkeneoPimClientInterface
     */
    public function getClient(): AkeneoPimClientInterface
    {
        if (!$this->client) {
            $akeneoClientBuilder = new AkeneoPimClientBuilder($this->credentials->getHost());
            $this->client = $akeneoClientBuilder->buildAuthenticatedByPassword(
                $this->credentials->getClientID(),
                $this->credentials->getClientSecret(),
                $this->credentials->getUser(),
                $this->credentials->getPassword(),
            );
        }

        return $this->client;
    }

    /**
     * @return CategoryDataProvider
     */
    public function getCategoryProvider(): CategoryDataProvider
    {
        return $this->category;
    }
}
