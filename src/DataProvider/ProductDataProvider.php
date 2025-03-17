<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Credentials;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Product;
use AmphiBee\AkeneoConnector\Service\AkeneoCredentialsBuilder;
use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ProductDataProvider extends AbstractDataProvider
{
    private ProductApiInterface $api;
    private Credentials $credentials;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->api = $client->getProductApi();
        $this->credentials = AkeneoCredentialsBuilder::getCredentials();

        parent::__construct();
    }

    /**
     * @param int   $pageSize
     * @param array $queryParameters
     *
     * @return Generator
     */
    public function getAll(int $pageSize = 10, array $queryParameters = []): Generator
    {
        if (!empty($this->credentials->getChannel())) {
            $queryParameters += ['scope' => $this->credentials->getChannel()];
        }
        foreach ($this->api->all($pageSize, $queryParameters) as $product) {
            try {
                yield $this->getSerializer()->denormalize($product, Product::class);
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize product (ProductCode %s) %s',
                    print_r($product, true),
                    $exception->getMessage()
                ));

                continue;
            }
        }
    }

    /**
     * Retrieve products by their identifiers.
     *
     * @param array $identifiers
     * @param int   $pageSize
     *
     * @return Generator
     */
    public function getProductsByIdentifiers(array $identifiers, int $pageSize = 10): Generator
    {
        if (empty($identifiers)) {
            return;
        }

        $queryParameters = [
            'search' => [
                'identifier' => [
                    ['operator' => 'IN', 'value' => $identifiers],
                ],
            ],
        ];

        if (!empty($this->credentials->getChannel())) {
            $queryParameters['scope'] = $this->credentials->getChannel();
        }

        $page = 1;
        do {
            $results = $this->api->listPerPage($pageSize, true, $queryParameters);

            foreach ($results->getItems() as $product) {
                try {
                    yield $this->getSerializer()->denormalize($product, Product::class);
                } catch (ExceptionInterface $exception) {
                    LoggerService::log(Logger::ERROR, sprintf(
                        'Cannot Denormalize product (Identifier %s): %s',
                        $product['identifier'] ?? 'unknown',
                        $exception->getMessage()
                    ));

                    continue;
                }
            }

            $page++;
        } while ($results->getNextPage());
    }
}
