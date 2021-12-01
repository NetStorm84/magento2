<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\ResourceModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Eav\Model\GetAttributeSetByName;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;

/**
 * Tests product resource model
 *
 * @see \Magento\Catalog\Model\ResourceModel\Product
 * @see \Magento\Catalog\Model\ResourceModel\AbstractResource
 */
class ProductTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Product
     */
    private $model;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->model = $this->objectManager->create(Product::class);
    }

    /**
     * Checks a possibility to retrieve product raw attribute value.
     *
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product with:{"sku": "simple"}
     */
    public function testGetAttributeRawValue()
    {
        $sku = 'simple';
        $attribute = 'name';

        $product = $this->productRepository->get($sku);
        $actual = $this->model->getAttributeRawValue($product->getId(), $attribute, null);
        self::assertEquals($product->getName(), $actual);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Attribute with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\AddAttributeToAttributeSet with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product with:{"sku": "simple"}
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function testGetAttributeRawValueGetDefault()
    {
        $product = $this->productRepository->get('simple', true, 0, true);
        $product->setCustomAttribute('prod_attr', 'default_value');
        $this->productRepository->save($product);

        $actual = $this->model->getAttributeRawValue($product->getId(), 'prod_attr', 1);
        $this->assertEquals('default_value', $actual);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Attribute with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\AddAttributeToAttributeSet with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product with:{"sku": "simple"}
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function testGetAttributeRawValueGetStoreSpecificValueNoDefault()
    {
        $product = $this->productRepository->get('simple', true, 0, true);
        $product->setCustomAttribute('prod_attr', null);
        $this->productRepository->save($product);

        $product = $this->productRepository->get('simple', true, 1, true);
        $product->setCustomAttribute('prod_attr', 'store_value');
        $this->productRepository->save($product);

        $actual = $this->model->getAttributeRawValue($product->getId(), 'prod_attr', 1);
        $this->assertEquals('store_value', $actual);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Attribute with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\AddAttributeToAttributeSet with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product with:{"sku": "simple"}
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function testGetAttributeRawValueGetStoreSpecificValueWithDefault()
    {
        $product = $this->productRepository->get('simple', true, 0, true);
        $product->setCustomAttribute('prod_attr', 'default_value');
        $this->productRepository->save($product);

        $product = $this->productRepository->get('simple', true, 1, true);
        $product->setCustomAttribute('prod_attr', 'store_value');
        $this->productRepository->save($product);

        $actual = $this->model->getAttributeRawValue($product->getId(), 'prod_attr', 1);
        $this->assertEquals('store_value', $actual);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Attribute with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\AddAttributeToAttributeSet with:{"attribute_code": "prod_attr"}
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product with:{"sku": "simple"}
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     * @throws NoSuchEntityException
     */
    public function testGetAttributeRawValueGetStoreValueFallbackToDefault()
    {
        $product = $this->productRepository->get('simple', true, 0, true);
        $product->setCustomAttribute('prod_attr', 'default_value');
        $this->productRepository->save($product);

        $actual = $this->model->getAttributeRawValue($product->getId(), 'prod_attr', 1);
        $this->assertEquals('default_value', $actual);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product as:product
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store catalog/price/scope 1
     * @magentoDataFixtureDataProvider productFixtureDataProvider
     */
    public function testUpdateStoreSpecificSpecialPrice()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productRepository->get('simple', true, 1);
        $this->assertEquals(5.99, $product->getSpecialPrice());

        $product->setSpecialPrice('');
        $this->model->save($product);
        $product = $this->productRepository->get('simple', false, 1, true);
        $this->assertEmpty($product->getSpecialPrice());

        $product = $this->productRepository->get('simple', false, 0, true);
        $this->assertEquals(5.99, $product->getSpecialPrice());
    }

    /**
     * @return array
     */
    public function productFixtureDataProvider(): array
    {
        return [
            'product' => [
                'sku' => 'simple',
                'special_price' => 5.99,
            ]
        ];
    }

    /**
     * Checks that product has no attribute values for attributes not assigned to the product's attribute set.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/attribute_set_with_image_attribute.php
     */
    public function testChangeAttributeSet()
    {
        $attributeCode = 'funny_image';
        /** @var GetAttributeSetByName $attributeSetModel */
        $attributeSetModel = $this->objectManager->get(GetAttributeSetByName::class);
        $attributeSet = $attributeSetModel->execute('attribute_set_with_media_attribute');

        $product = $this->productRepository->get('simple', true, 1, true);
        $product->setAttributeSetId($attributeSet->getAttributeSetId());
        $this->productRepository->save($product);
        $product->setData($attributeCode, 'test');
        $this->model->saveAttribute($product, $attributeCode);

        $product = $this->productRepository->get('simple', true, 1, true);
        $this->assertEquals('test', $product->getData($attributeCode));

        $product->setAttributeSetId($product->getDefaultAttributeSetId());
        $this->productRepository->save($product);

        $attribute = $this->model->getAttributeRawValue($product->getId(), $attributeCode, 1);
        $this->assertEmpty($attribute);
    }
}
