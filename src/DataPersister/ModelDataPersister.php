<?php

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\DataPersister;

use AmphiBee\AkeneoConnector\Entity\WooCommerce\Model;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ModelDataPersister extends AbstractDataPersister
{
    /**
     * @param Model $model
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @todo remove suppress warning
     */
    public function createOrUpdate(Model $model): void
    {
        try {
            // $modelAsArray = $this->getSerializer()->normalize($model);
            //@todo save in WC
        } catch (ExceptionInterface $e) {
            LoggerService::log(Logger::ERROR, sprintf(
                'Cannot Normalize Model (ModelCode %s) %s',
                print_r($model, true),
                $e->getMessage()
            ));

            return;
        }
    }
}
