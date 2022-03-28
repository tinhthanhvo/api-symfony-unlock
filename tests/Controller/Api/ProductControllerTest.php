<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\ProductFixtures;
use App\Entity\Product;
use App\Tests\Controller\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ProductControllerTest extends BaseWebTestCase
{
    private $productRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->entityManager->getRepository(Product::class);
    }

    public function testGetProduct()
    {
        $productFixture = new ProductFixtures();
        $this->loadFixture($productFixture);
        $product = $this->productRepository->findOneBy(['name' => 'Product name']);

        $this->client->request(
            Request::METHOD_GET,
            '/api/products/' . $product->getId(),
            [],
            [],
            ['HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('Product name', $data['name']);
        $this->assertSame('Color name', $data['color']);
        $this->assertSame('Product description', $data['description']);
        $this->assertIsArray($data['items']);
        $this->assertIsArray($data['gallery']);
    }
}
