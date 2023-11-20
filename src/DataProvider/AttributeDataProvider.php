<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Helpers\Translator;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute as AK_Attribute;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Attribute as WP_Attribute;

class AttributeDataProvider extends AbstractDataProvider
{
    /**
     * The API instance
     */
    private AttributeApiInterface $api;

    /**
     * Store values in here to avoid useless API queries.
     */
    private static $attributes = [];

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
        $this->api = $client->getAttributeApi();

        parent::__construct();
    }


    /**
     * Query API first time to store attributes type & labels
     *
     * @return array
     */
    public static function getAttributes()
    {
        if (!isset(self::$attributes)) {
            self::$attributes = [];

            $provider = AkeneoClientBuilder::create()->getAttributeProvider();

            foreach ($provider->getAll() as $attribute) {
                $labels       = $attribute->getLabels();
                $group_labels = $attribute->getGroupLabels();

                if (empty($labels)) {
                    $labels = $group_labels;
                }

                self::$attributes[$attribute->getCode()] = [
                    'type'  => $attribute->getType(),
                    'label' => $labels,
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

    public static function getAttributeLabel($attrCode, $locale = '')
    {
        if (!$locale) {
            $locale = (new Translator())->default;
        }

        $attributes = self::getAttributes();
        return $attributes[$attrCode]['label'][$locale] ?? $attrCode;
    }


    /**
     * @param int   $pageSize
     * @param array $queryParameters
     *
     * @return Generator
     */
    public function getAll(int $pageSize = 10, array $queryParameters = []): Generator
    {
        foreach ($this->api->all($pageSize, $queryParameters) as $attribute) {
            try {
                $prepare = $this->prepare($attribute);
                yield $this->getSerializer()->denormalize($prepare, AK_Attribute::class);
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

        $target = apply_filters("ak/import/single/target/attribute/type={$type}", $target);

        // TODO: Create & Read target settings /!\

        $target = apply_filters("ak/import/single/target/attribute/type={$type}/code={$code}", $target);

        return $target;
    }

    protected function prepare($attribute): array
    {
        $prepare = [
            'code'                   => $attribute['code'],
            'type'                   => $attribute['type'],
            'localizable'            => $attribute['localizable'],
            'group'                  => $attribute['group'],
            'labels'                 => $attribute['labels'],
            'group_labels'           => $attribute['group_labels'],
            'variant_attribute_sets' => $attribute['variant_attribute_sets'] ?? '',
            'target'                 => $this->getConversionTarget($attribute['type'], $attribute['code']),
        ];

        $metas_datas = array_diff_key($attribute, $prepare);

        $prepare['meta_datas'] = $metas_datas;

        return $prepare;
    }
}
