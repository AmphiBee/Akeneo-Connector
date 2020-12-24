<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Model as AkeneoModel;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Model as WooCommerceModel;

class ModelAdapter
{

    /**
     * @param AkeneoModel $akeneoModel
     *
     * @return WooCommerceModel
     */
    public function getWordpressModel(AkeneoModel $akeneoModel): WooCommerceModel
    {
        $model = new WooCommerceModel($akeneoModel->getCode());
        $model->setParent($akeneoModel->getParent());

        //@todo adapt others needed things

        return $model;
    }
}
