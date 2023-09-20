<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AK_Attribute;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Generator;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class FamilyVariantDataProvider extends AttributeDataProvider
{
    private FamilyVariantApiInterface $familyVariantApi;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->familyVariantApi = $client->getFamilyVariantApi();

        parent::__construct($client);
    }

    /**
     * @param string $familyCode
     * @param int    $pageSize
     * @param array  $queryParameters
     *
     * @return Generator
     */
    public function getAll(int $pageSize = 10, array $queryParameters = []): Generator
    {
        if (empty($queryParameters['familyCode'])) {
            return [];
        }
        foreach ($this->familyVariantApi->all($queryParameters['familyCode'], $pageSize, $queryParameters) as $variant) {
            try {
                // Default values for attributes
                $variant['type'] = 'pim_catalog_simpleselect';
                $variant['localizable'] = true;
                $variant['group'] = '';
                $variant['group_labels'] = $variant['labels'];

                $prepare = $this->prepare($variant);

                yield $this->getSerializer()->denormalize($prepare, AK_Attribute::class);
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize family variant (Option Code %s) %s',
                    print_r($variant, true),
                    $exception->getMessage()
                ));

                continue;
            }
        }
    }

    public function get($familyCode, $familyVariantCode)
    {
        return $this->familyVariantApi->get($familyCode, $familyVariantCode);
    }
}
