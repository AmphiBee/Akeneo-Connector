<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Product;
use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ProductDataProvider extends AbstractDataProvider
{
    private ProductApiInterface $api;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->api = $client->getProductApi();

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
        foreach ($this->api->all($pageSize, $queryParameters) as $product) {
            try {
                // dd($product);
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
}
