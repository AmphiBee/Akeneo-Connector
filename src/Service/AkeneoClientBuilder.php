<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service;

use AmphiBee\AkeneoConnector\DataProvider\FamilyDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\FamilyVariantDataProvider;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientBuilder;
use AmphiBee\AkeneoConnector\Service\Akeneo\AkeneoPimClientInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Credentials;
use AmphiBee\AkeneoConnector\DataProvider\ProductDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\CategoryDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\AttributeDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\ProductModelDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\AttributeOptionDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\Enterprise\ReferenceEntityDataProvider;
use AmphiBee\AkeneoConnector\DataProvider\CustomReferenceDataProvider;

class AkeneoClientBuilder
{
    private ?AkeneoPimClientInterface $client = null;
    private ?AkeneoPimEnterpriseClientInterface $enterprise_client = null;

    private Credentials $credentials;

    # Akaneo endpoints
    private CategoryDataProvider $category;
    private AttributeDataProvider $attribute;
    private AttributeOptionDataProvider $attributeOption;
    private FamilyDataProvider $family;
    private FamilyVariantDataProvider $familyVariant;
    private ProductModelDataProvider $productModel;
    private ProductDataProvider $product;

    # Custom endpoints
    private CustomReferenceDataProvider $customReferenceData;

    /**
     * AkeneoClientBuilder constructor.
     */
    public function __construct()
    {
        $this->credentials = AkeneoCredentialsBuilder::getCredentials();

        # General
        $this->category        = new CategoryDataProvider($this->getClient());
        $this->attribute       = new AttributeDataProvider($this->getClient());
        $this->attributeOption = new AttributeOptionDataProvider($this->getClient());
        $this->family          = new FamilyDataProvider($this->getClient());
        $this->familyVariant   = new FamilyVariantDataProvider($this->getClient());
        $this->productModel    = new ProductModelDataProvider($this->getClient());
        $this->product         = new ProductDataProvider($this->getClient());

        # Enterprise
        // $this->referenceEntity = new ReferenceEntityDataProvider($this->getEnterpriseClient());

        # Custom endpoints
        $this->customReferenceData = new CustomReferenceDataProvider($this->getClient());
    }

    /**
     * @return AkeneoClientBuilder
     */
    public static function create(): AkeneoClientBuilder
    {
        return new static();
    }

    /**
     * @return AkeneoPimClientInterface
     */
    public function getClient(): AkeneoPimClientInterface
    {
        if (!$this->client) {
            $akeneoClientBuilder = new AkeneoPimClientBuilder($this->credentials->getHost());
            $this->client = $akeneoClientBuilder->buildAuthenticatedByPassword(
                $this->credentials->getClientID(),
                $this->credentials->getClientSecret(),
                $this->credentials->getUser(),
                $this->credentials->getPassword(),
            );
        }

        return $this->client;
    }

    /**
     * @return AkeneoPimEnterpriseClientInterface
     */
    public function getEnterpriseClient(): AkeneoPimEnterpriseClientInterface
    {
        if (!$this->enterprise_client) {
            $akeneoClientBuilder = new AkeneoPimEnterpriseClientBuilder($this->credentials->getHost());
            $this->enterprise_client = $akeneoClientBuilder->buildAuthenticatedByPassword(
                $this->credentials->getClientID(),
                $this->credentials->getClientSecret(),
                $this->credentials->getUser(),
                $this->credentials->getPassword(),
            );
        }

        return $this->enterprise_client;
    }

    /**
     * @return CategoryDataProvider
     */
    public function getCategoryProvider(): CategoryDataProvider
    {
        return $this->category;
    }

    /**
     * @return AttributeDataProvider
     */
    public function getAttributeProvider(): AttributeDataProvider
    {
        return $this->attribute;
    }

    /**
     * @return AttributeOptionDataProvider
     */
    public function getAttributeOptionProvider(): AttributeOptionDataProvider
    {
        return $this->attributeOption;
    }

    public function getFamilyProvider(): FamilyDataProvider
    {
        return $this->family;
    }

    /**
     * @return FamilyVariantDataProvider
     */
    public function getFamilyVariantProvider(): FamilyVariantDataProvider
    {
        return $this->familyVariant;
    }

    /**
     * @return ProductModelDataProvider
     */
    public function getProductModelProvider(): ProductModelDataProvider
    {
        return $this->productModel;
    }

    /**
     * @return ProductDataProvider
     */
    public function getProductProvider(): ProductDataProvider
    {
        return $this->product;
    }

    /**
     * @return ReferenceEntityDataProvider
     */
    public function getReferenceEntityProvider(): ReferenceEntityDataProvider
    {
        return $this->referenceEntity;
    }

    /**
     * @return CustomReferenceDataProvider
     */
    public function getCustomReferenceDataProvider(): CustomReferenceDataProvider
    {
        return $this->customReferenceData;
    }
}
