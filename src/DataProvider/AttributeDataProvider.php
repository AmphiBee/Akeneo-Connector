<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Category;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Generator;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AttributeDataProvider extends AbstractDataProvider
{
    private AttributeApiInterface $attributeApi;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->attributeApi = $client->getAttributeApi();

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
        foreach ($this->attributeApi->all($pageSize, $queryParameters) as $attribute) {
            try {
                $attribute = $this->getSerializer()->denormalize($attribute, Attribute::class);

                yield $attribute;
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize attribute (AttrCode %s) %s',
                    print_r($attribute, true),
                    $exception->getMessage()
                ));
                continue;
            }
        }
    }
}
