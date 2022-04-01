<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CartFixtures;
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

    public function testGetCarts(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $user = $this->userRepository->findOneBy(['email' => 'user_mail@gmail.com']);
        $this->client->request(
            Request::METHOD_GET,
            '/api/carts',
            [],
            [],
            ['HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE],
            json_encode(['userId' => $user->getId()])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data['success']);
        $this->assertCount(2, $data['success']);

        // Test filter and offset
        $this->client->request(
            Request::METHOD_GET,
            '/api/carts?limit=1&offset=1',
            [],
            [],
            ['HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE],
            json_encode(['userId' => $user->getId()])
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
