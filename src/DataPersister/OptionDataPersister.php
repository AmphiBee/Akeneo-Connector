<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use Monolog\Logger;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Option;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class OptionDataPersister extends AbstractDataPersister
{
    /**
     * @param Option $option
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdateOption(Option $option): void
    {
        try {
            $optionAsArray = $this->getSerializer()->normalize($option);

            //@todo save in WC
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Option (OptCode %s) %s',
                print_r($option, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
