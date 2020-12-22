<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataProvider;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Category;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Generator;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use Monolog\Logger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class CategoryDataProvider extends AbstractDataProvider
{
    private CategoryApiInterface $categoryApi;

    /**
     * Category constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->categoryApi = $client->getCategoryApi();

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
        foreach ($this->categoryApi->all($pageSize, $queryParameters) as $category) {
            try {
                $category = $this->getSerializer()->denormalize($category, Category::class);

                yield $category;
            } catch (ExceptionInterface $e) {
                LoggerService::log(Logger::ERROR, sprintf(
                    'Cannot Denormalize category (Category Code %s) %s',
                    print_r($category, true),
                    $e->getMessage()
                ));

                continue;
            }
        }
    }
}
