<?php

namespace AmphiBee\AkeneoConnector\Service\Akeneo;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface as BaseAkeneoPimClientInterface;
use AmphiBee\AkeneoConnector\Api\CustomReferenceDataApiInterface;

/**
 * Client to use the Akeneo PIM API.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface AkeneoPimClientInterface extends BaseAkeneoPimClientInterface
{
    public function getCustomReferenceDataApi(): CustomReferenceDataApiInterface;
}
