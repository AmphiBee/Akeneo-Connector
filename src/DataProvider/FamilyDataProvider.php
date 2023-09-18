<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Family;
use Generator;
use Monolog\Logger;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AK_Attribute;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute as WP_Attribute;

class FamilyDataProvider extends AbstractDataProvider
{
    /**
     * The API instance
     */
    private FamilyApiInterface $api;

    /**
     * Store values in here to avoid useless API queries.
     */
    private static $families = [];

    /**
     * The default conversion behaviour for this entity
     */
    protected static string $default_target = WP_Attribute::class;


    /**
     * Class constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->api = $client->getFamilyApi();

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
        foreach ($this->api->all($pageSize, $queryParameters) as $family) {
            try {
                $prepare = [
                    'code'         => $family['code'],
                ];

                yield $this->getSerializer()->denormalize($prepare, Family::class);
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize attribute (Family %s) %s',
                    print_r($family, true),
                    $exception->getMessage()
                ));
                continue;
            }
        }
    }
}
