<?php

namespace AmphiBee\AkeneoConnector\Api;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;

interface CustomReferenceDataApiInterface
{
    public function get(string $code): array;

    public function all(array $uriParameters = [], array $queryParameters = []);


    // public function listPerPage(int $limit = 10, bool $withCount = false, array $queryParameters = []): PageInterface;
    //public function all(int $pageSize = 10, array $queryParameters = []): ResourceCursorInterface;
}
