<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Model;
use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ProductModelDataProvider extends AbstractDataProvider
{
    private ProductModelApiInterface $productModelApi;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->productModelApi = $client->getProductModelApi();

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
        foreach ($this->productModelApi->all($pageSize, $queryParameters) as $model) {
            try {
                $model = $this->getSerializer()->denormalize($model, Model::class);
                
                yield $model;
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize model (ModelCode %s) %s',
                    print_r($model, true),
                    $exception->getMessage()
                ));

                continue;
            }
        }
    }
}
