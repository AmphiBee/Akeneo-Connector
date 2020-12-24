<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Option;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Generator;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AttributeOptionDataProvider extends AbstractDataProvider
{
    private AttributeOptionApiInterface $attributesOptionsApi;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->attributesOptionsApi = $client->getAttributeOptionApi();

        parent::__construct();
    }

    /**
     * @param string $attrCode
     * @param int    $pageSize
     * @param array  $queryParameters
     *
     * @return Generator
     */
    public function getAll(string $attrCode, int $pageSize = 10, array $queryParameters = []): Generator
    {
        foreach ($this->attributesOptionsApi->all($attrCode, $pageSize, $queryParameters) as $option) {
            try {
                yield $this->getSerializer()->denormalize($option, Option::class);
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize attributeOption (Option Code %s) %s',
                    print_r($option, true),
                    $exception->getMessage()
                ));

                continue;
            }
        }
    }
}
