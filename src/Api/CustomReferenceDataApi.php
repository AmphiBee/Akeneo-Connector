<?php

namespace AmphiBee\AkeneoConnector\Api;

use Akeneo\Pim\ApiClient\Client\ResourceClientInterface;
use Akeneo\Pim\ApiClient\Exception\InvalidArgumentException;
use Akeneo\Pim\ApiClient\Pagination\PageFactoryInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorFactoryInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;

/**
 * API implementation to manage the attributes.
 *
 * @author    Laurent Petard <laurent.petard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CustomReferenceDataApi implements CustomReferenceDataApiInterface
{
    const CRDATAS_URI = 'api/rest/v1/reference-data/getAll';
    const CRDATA_URI  = 'api/rest/v1/reference-data/get/%s';

    /** @var ResourceClientInterface */
    protected $resourceClient;

    /** @var PageFactoryInterface */
    protected $pageFactory;

    /** @var ResourceCursorFactoryInterface */
    protected $cursorFactory;

    /**
     * @param ResourceClientInterface        $resourceClient
     * @param PageFactoryInterface           $pageFactory
     * @param ResourceCursorFactoryInterface $cursorFactory
     */
    public function __construct(
        ResourceClientInterface $resourceClient,
        PageFactoryInterface $pageFactory,
        ResourceCursorFactoryInterface $cursorFactory
    ) {
        $this->resourceClient = $resourceClient;
        $this->pageFactory = $pageFactory;
        $this->cursorFactory = $cursorFactory;
    }

    public function get(string $code): array
    {
        try {
            return $this->resourceClient->getResource(static::CRDATA_URI, [$code]);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function list(array $uriParameters = [], array $queryParameters = [])
    {
        return $this->resourceClient->getResource(static::CRDATAS_URI, $uriParameters, $queryParameters);
    }

    public function all(array $uriParameters = [], array $queryParameters = []): array
    {
        $all  = [];
        $list = $this->list();

        foreach ($list as $single) {
            $all[$single] = $this->get($single);
        }

        return $all;
    }
}
