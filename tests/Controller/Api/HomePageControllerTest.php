<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;
use App\Entity\Product;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomePageControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $productRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->entityManager->getRepository(Product::class);
    }

    public function testGetProduct(): void
    {
        $productFixture = new ProductFixtures();
        $this->loadFixture($productFixture);
        $product = $this->productRepository->findOneBy(['name' => 'Product name 1']);

        $this->client->request(
            Request::METHOD_GET,
            '/api/products/' . $product->getId(),
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('Product name 1', $data['name']);
        $this->assertSame('Color name', $data['color']);
        $this->assertSame('Product description 1', $data['description']);
        $this->assertIsArray($data['items']);
        $this->assertIsArray($data['gallery']);
    }

    public function testGetProducts(): void
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/products',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $product = $data[0];
        $this->assertSame('Product name 1', $product['name']);
        $this->assertSame('300000', $product['price']);
    }

    public function testFilterByCondition(): void
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $payload = [
            'category' => 1,
            'color' => 1,
            'priceFrom' => 400000,
            'priceTo' => 500000,
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/products/filter',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data['data']);
        $this->assertCount(1, $data['data']);

        $product = $data['data'][0];
        $this->assertSame('Product name 2', $product['name']);
        $this->assertSame('500000', $product['price']);
    }

    /**
     * @return void
     */
    public function testGetCategories(): void
    {
        $categoryFixtures = new CategoryFixtures();
        $this->loadFixture($categoryFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/categories',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        $category = $data[0];
        $this->assertSame('Category name', $category['name']);
    }
}
