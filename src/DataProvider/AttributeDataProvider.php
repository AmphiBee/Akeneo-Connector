<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Generator;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AttributeDataProvider extends AbstractDataProvider
{
    private AttributeApiInterface $attributeApi;
    private static $attributes = null;

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

    public static function getAttributes()
    {
        if (is_null(self::$attributes)) {
            self::$attributes = [];
            $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();
            foreach ($attributeDataProvider->getAll() as $attribute) {
                $labels = $attribute->getLabels();

                // @todo polylang
                $language = 'fr_FR';
                $attributeName = $labels[$language];

                self::$attributes[$attribute->getCode()] = [
                    'type' => $attribute->getType(),
                    'label' => $attributeName,
                ];
            }
        }
        return self::$attributes;
    }

    public static function getAttributeType($attrCode)
    {
        $attributes = self::getAttributes();

        return isset($attributes[$attrCode]) ? $attributes[$attrCode]['type'] : 'pim_catalog_text';
    }

    public static function getAttributeLabel($attrCode)
    {
        $attributes = self::getAttributes();
        return isset($attributes[$attrCode]) ? $attributes[$attrCode]['label'] : $attrCode;
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
                yield $this->getSerializer()->denormalize($attribute, Attribute::class);
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
