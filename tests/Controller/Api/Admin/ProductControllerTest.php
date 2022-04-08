<?php

namespace App\Tests\Controller\Api\Admin;

use App\DataFixtures\ProductFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Product;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $productRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->productRepository = $this->entityManager->getRepository(Product::class);
    }

    public function testGetProducts(): void
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            '/api/admin/products',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $products = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($products);
        $this->assertCount(2, $products['data']);

        $product = $products['data'][0];
        $this->assertSame('Product name 2', $product['name']);
        $this->assertSame('500000', $product['price']);
    }

    public function testGetProduct(): void
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);
        $product = $this->productRepository->findOneBy(['name' => 'Product name 1']);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            '/api/admin/products/' . $product->getId(),
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('Product name 1', $data['name']);
        $this->assertSame('Category name', $data['category']);
        $this->assertSame('Color name', $data['color']);
        $this->assertSame('Product description 1', $data['description']);
        $this->assertIsArray($data['items']);
        $this->assertIsArray($data['gallery']);
    }
}
