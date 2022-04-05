<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CartFixtures;
use App\DataFixtures\ProductItemFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Cart;
use App\Entity\ProductItem;
use App\Entity\User;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $cartRepository;
    private $userRepository;
    private $productItemRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = $this->entityManager->getRepository(Cart::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->productItemRepository = $this->entityManager->getRepository(ProductItem::class);
    }

    public function testCountCartItems(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/carts/count',
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
        $this->assertEquals(2, $data['count']);
    }

    public function testGetCartItems(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/carts',
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
        $this->assertCount(2, $data);

        // Test filter and offset
        $this->client->request(
            Request::METHOD_GET,
            '/api/users/carts?limit=1&offset=1',
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
        $this->assertCount(1, $data);

        $product = $data[0];
        $this->assertSame('Product name 2', $product['name']);
        $this->assertEquals(500000, $product['price']);
    }

    public function testInsertCartItem(): void
    {
        $userFixtures = new UserFixtures();
        $this->loadFixture($userFixtures);

        $productItemFixtures = new ProductItemFixtures();
        $this->loadFixture($productItemFixtures);

        $user = $this->userRepository->findOneBy(['email' => 'user@gmail.com']);
        $productItem = $this->productItemRepository->findOneBy(['amount' => 10]);
        $payload = [
            "productItem" => $productItem->getId(),
            "amount" => 1,
            "price" => 365000
        ];
        $this->client->request(
            Request::METHOD_POST,
            '/api/users/carts',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $cartItems = $this->cartRepository->findBy(['user' => $user->getId()]);
        $this->assertNotEmpty($cartItems);
        $this->assertCount(1, $cartItems);
        $this->assertEquals($productItem->getId(), $cartItems[0]->getProductItem()->getId());
        $this->assertEquals(1, $cartItems[0]->getAmount());
        $this->assertEquals(365000, $cartItems[0]->getPrice());
    }

    public function testUpdateCartItem(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $user = $this->userRepository->findOneBy(['email' => 'user@gmail.com']);
        $cartItem = $this->cartRepository->findOneBy(['price' => 300000]);
        $payload = [
            "amount" => 2,
            "price" => 400000
        ];
        $this->client->request(
            Request::METHOD_PUT,
            '/api/users/carts/' . $cartItem->getId(),
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        $this->entityManager->refresh($cartItem);
        $this->assertEquals($user->getId(), $cartItem->getUser()->getId());
        $this->assertEquals($payload['amount'], $cartItem->getAmount());
        $this->assertEquals($payload['price'], $cartItem->getPrice());
    }

    public function testRemoveCartItem(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $user = $this->userRepository->findOneBy(['email' => 'user@gmail.com']);
        $cartItem = $this->cartRepository->findOneBy(['user' => $user->getId()]);

        $this->client->request(
            Request::METHOD_DELETE,
            '/api/users/carts/' . $cartItem->getId(),
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        $dataAfterDelete = $this->cartRepository->findBy(['user' => $user->getId()]);
        $this->assertIsArray($dataAfterDelete);
        $this->assertCount(1, $dataAfterDelete);
    }
}
