<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CartFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Cart;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = $this->entityManager->getRepository(Cart::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testGetCartItems(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $userFixture = new UserFixtures();
        $this->loadFixture($userFixture);
        dd($cartFixtures);

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

        $this->assertIsArray($data['success']);
        $this->assertCount(2, $data['success']);

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
        $this->assertIsArray($data['success']);
        $this->assertCount(1, $data['success']);

        $product = $data['success'][0];
        $this->assertSame('Product name 2', $product['name']);
        $this->assertSame('500000', $product['price']);
    }
}
