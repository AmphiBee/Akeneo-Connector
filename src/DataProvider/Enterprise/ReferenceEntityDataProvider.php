<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider\Enterprise;

use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\DataProvider\AbstractDataProvider;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityApiInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

class ReferenceEntityDataProvider extends AbstractDataProvider
{
    private ReferenceEntityApiInterface $referenceEntityApi;

    private static $referenceEntities = null;

    /**
     * Category constructor.
     *
     * @param AkeneoPimEnterpriseClientInterface $client
     */
    public function __construct(AkeneoPimEnterpriseClientInterface $client)
    {
        $this->referenceEntityApi = $client->getReferenceEntityApi();

        parent::__construct();
    }

    public static function getReferenceEntities()
    {
        if (is_null(self::$referenceEntities)) {
            self::$referenceEntities = [];

            $provider = AkeneoClientBuilder::create()->getReferenceEntityProvider();

            foreach ($provider->getAll() as $refEntity) {
                $labels       = $refEntity->getLabels();
                $group_labels = $refEntity->getGroupLabels();

                if (empty($labels)) {
                    $labels = $group_labels;
                }

                // @todo polylang
                $language = 'fr_FR';
                $fallback = 'en_US';

                $name = $labels[$language] ?? ($labels[$fallback] ?? '');

                self::$referenceEntities[$refEntity->getCode()] = [
                    'type' => $refEntity->getType(),
                    'label' => $name,
                ];
            }
        }
        return self::$referenceEntities;
    }

    public static function getAttributeType($attrCode)
    {
        $referenceEntities = self::getReferenceEntities();

        return isset($referenceEntities[$attrCode]) ? $referenceEntities[$attrCode]['type'] : 'pim_catalog_text';
    }

    public static function getAttributeLabel($attrCode)
    {
        $referenceEntities = self::getReferenceEntities();
        return isset($referenceEntities[$attrCode]) ? $referenceEntities[$attrCode]['label'] : $attrCode;
    }

    /**
     * @param int   $pageSize
     * @param array $queryParameters
     *
     * @return Generator
     */
    public function getAll(array $queryParameters = []): Generator
    {
        foreach ($this->referenceEntityApi->all($queryParameters) as $attribute) {
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
