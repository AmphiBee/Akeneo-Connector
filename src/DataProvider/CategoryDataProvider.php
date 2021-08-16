<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use Generator;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AK_Category;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Category as WP_Category;

class CategoryDataProvider extends AbstractDataProvider
{
    /**
     * The API instance
     */
    private CategoryApiInterface $api;


    /**
     * The default conversion behaviour for this entity
     */
    protected static string $default_target = WP_Category::class;


    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->api = $client->getCategoryApi();

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
        foreach ($this->api->all($pageSize, $queryParameters) as $category) {
            try {
                $prepare = [
                    'code'                  => $category['code'],
                    'parent'                => $category['parent'],
                    'labels'                => $category['labels'],
                    'description'           => $category['description'],
                    'descriptionEN'         => $category['descriptionEN'],
                    'categoryContentText'   => $category['categoryContentText'],
                    'categoryContentTextEN' => $category['categoryContentTextEN'],
                    'miniature'             => $category['miniature'],
                    'categoryContentImage'  => $category['categoryContentImage'],
                    'target'                => $this->getConversionTarget($category['code']),
                ];

                $metas_datas = array_diff_key($category, $prepare);

                $prepare['meta_datas'] = $metas_datas;

                yield $this->getSerializer()->denormalize($prepare, AK_Category::class);
            } catch (ExceptionInterface $exception) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize category (CategoryCode %s) %s',
                    print_r($category, true),
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
    public function getConversionTarget($code): string
    {
        $target = static::$default_target;

        // TODO: Create & Read target settings /!\

        $target = apply_filters("ak/f/import/single/target/category/code={$code}", $target);

        return $target;
    }
}
