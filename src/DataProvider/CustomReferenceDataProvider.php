<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Akeneo\Pim\ApiClient\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\DataProvider\AbstractDataProvider;
use AmphiBee\AkeneoConnector\Api\CustomReferenceDataApiInterface;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\CustomReferenceData;
use AmphiBee\AkeneoConnector\Entity\WordPress\Attribute as WP_Attribute;
use AmphiBee\AkeneoConnector\Entity\WordPress\Option as WP_Option;

class CustomReferenceDataProvider extends AbstractDataProvider
{
    /**
     * Api instance
     */
    protected CustomReferenceDataApiInterface $customReferenceDataApi;

    /**
     *
     */
    protected static $referenceEntities = null;

    /**
     * The transient key for caching purpose.
     */
    protected static string $transient_name = 'ak.custom-reference-data.parsed';


    /**
     * The default conversion behaviour for this entity
     */
    protected static string $default_target = WP_Option::class;


    /**
     * Class constructor.
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->customReferenceDataApi = $client->getCustomReferenceDataApi();

        parent::__construct();
    }


    /**
     *
     */
    public static function getCustomReferenceDatas()
    {
        if (is_null(self::$referenceEntities)) {
            self::$referenceEntities = [];

            $provider = AkeneoClientBuilder::create()->getCustomReferenceDataProvider();

            foreach ($provider->getAllArray() as $refEntity) {
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


    /**
     * Store the given data in cache.
     *
     * @param array $data
     *
     * @return bool True if the value was set, false otherwise.
     */
    public static function setCacheData($data)
    {
        $provider = AkeneoClientBuilder::create()->getCustomReferenceDataProvider();

        $data = $provider->groupNormalize($data);

        return set_transient(static::$transient_name, $data, 12 * HOUR_IN_SECONDS);
    }


    /**
     * Get parsed data from database transcient.
     * If transcient is expired, query API and store in cache.
     *
     * @param bool $forced_refresh If set to true, cached data will be ignored and an api call will be made. Default to false.
     *
     * @return array
     */
    public static function getCacheData(bool $forced_refresh = false)
    {
        $provider = AkeneoClientBuilder::create()->getCustomReferenceDataProvider();
        $data     = [];

        # Return cache data if exists
        if (!$forced_refresh && ($data = get_transient(static::$transient_name))) {
            # denormalize cache into entity class
            return $provider->groupDenormalize($data);
        }

        # Parse data
        foreach ($provider->getAllArray() as $ak_ref_data) {
            $data[$ak_ref_data->getType()][] = $ak_ref_data;
        }

        static::setCacheData($data);

        return $data;
    }


    /**
     * Query API to retrieve all reference datas for ALL OPTIONS.
     *
     * @param array   $uriParameters
     * @param array $queryParameters
     *
     * @return Generator
     */
    public function getAllArray(array $uriParameters = [], array $queryParameters = []): Generator
    {
        foreach ($this->customReferenceDataApi->all($uriParameters, $queryParameters) as $type => $ref_datas) {
            foreach ($ref_datas as $ref_data) {
                try {
                    $prepare = [
                        'type'    => $type,
                        'code'    => $ref_data['code'],
                        'labels'  => $ref_data['labels'],
                        'target' => $this->getConversionTarget($type, $ref_data['code']),
                    ];

                    $metas_datas = array_diff_key($ref_data, $prepare);

                    $prepare['meta_datas'] = $metas_datas;

                    yield $this->getSerializer()->denormalize($prepare, CustomReferenceData::class);
                } catch (ExceptionInterface $exception) {
                    LoggerService::log(Logger::ERROR, sprintf(
                        'Cannot Denormalize Custom Reference data (AttrCode %s) %s',
                        print_r($ref_data, true),
                        $exception->getMessage()
                    ));
                    continue;
                }
            }
        }
    }


    /**
     * Query API to retrieve all reference datas for a given name.
     *
     * @param array  $code              The Reference Data name
     *
     * @return Generator
     */
    public function getAll($name): Generator
    {
        foreach ($this->customReferenceDataApi->get($name) as $ref_data) {
            try {
                $prepare = [
                    'type'    => $name,
                    'name'    => $name,
                    'code'    => $ref_data['code'],
                    'labels'  => $ref_data['labels'],
                    'target' => $this->getConversionTarget($name, $ref_data['code']),
                ];

                $metas_datas = array_diff_key($ref_data, $prepare);

                $prepare['meta_datas'] = $metas_datas;

                yield $this->getSerializer()->denormalize($prepare, CustomReferenceData::class);
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize Custom Reference data (AttrCode %s) %s',
                    print_r($ref_data, true),
                    $exception->getMessage()
                ));
                continue;
            }
        }
    }


    /**
     * Determine wich target to use for entity conversion from Akaneo to Wordpress
     *
     * @param string $type The Reference data name
     * @param string $code The Reference data item code
     *
     * @return string The target entity class
     */
    public function getConversionTarget($type, $code): string
    {
        $target = static::$default_target;

        $target = apply_filters("ak/import/single/target/custom-reference-data/{$type}", $target);

        // TODO: Read settings /!\

        $target = apply_filters("ak/import/single/target/custom-reference-data/{$type}/{$code}", $target);

        return $target;
    }


    /**
     * Normalize entries into classes.
     *
     * @return array
     */
    public function groupNormalize(array $data, $serializer = null)
    {
        if (!$serializer) {
            $serializer = $this->getSerializer();
        }

        foreach ($data as $key => $values) {
            $data[$key] = array_map([$serializer, 'normalize'], $values);
        }

        return $data;
    }


    /**
     * Denormalize entries into arrays.
     *
     * @return array
     */
    public function groupDenormalize(array $data, $serializer = null)
    {
        if (!$serializer) {
            $serializer = $this->getSerializer();
        }

        foreach ($data as $key => $values) {
            $data[$key] = collect($values)->map(function ($i) use ($serializer) {
                return $serializer->denormalize($i, CustomReferenceData::class);
            })->toArray();
        }

        return $data;
    }
}
