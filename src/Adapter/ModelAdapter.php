<?php declare(strict_types=1);

/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Adapter;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Model as AK_Model;
use AmphiBee\AkeneoConnector\Entity\WooCommerce\Model as WP_Model;

class ModelAdapter
{
    /**
     * Get WP model from AK model
     */
    public function fromModel(AK_Model $ak_model): WP_Model
    {
        $model = new WP_Model($ak_model->getCode());

        $model->setParent($ak_model->getParent());
        $model->setFamily($ak_model->getFamily());
        $model->setFamilyVariant($ak_model->getFamilyVariant());

        $model->setValues($ak_model->getValues());
        $model->setCategories($ak_model->getCategories());
        $model->setAssociation($ak_model->getAssociations());

        return $model;
    }

    /**
     * @deprecated use fromModel instead
     */
    public function getWordpressModel(AK_Model $ak_model): WP_Model
    {
        return $this->fromModel($ak_model);
    }
}
