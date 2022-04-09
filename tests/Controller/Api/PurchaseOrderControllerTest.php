<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CartFixtures;
use App\DataFixtures\PurchaseOrderFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Cart;
use App\Entity\PurchaseOrder;
use App\Entity\User;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $userRepository;
    private $cartRepository;
    private $purchaseOrderRepository;


    public function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = $this->entityManager->getRepository(Cart::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->purchaseOrderRepository = $this->entityManager->getRepository(PurchaseOrder::class);
    }

    public function testGetOrdersAction(): void
    {
        $purchaseOrder = new PurchaseOrderFixtures();
        $this->loadFixture($purchaseOrder);

        $this->client->request(
            Request::METHOD_GET,
            'api/users/orders',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $orderList = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($orderList);
        $this->assertCount(1, $orderList['data']);
        $order = $orderList['data'][0];
        $this->assertSame('Recipient Name', $order['recipientName']);
        $this->assertSame('Recipient Email', $order['recipientEmail']);
        $this->assertSame('0123456789', $order['recipientPhone']);
        $this->assertSame('Cai Khe, Ninh Kieu', $order['addressDelivery']);
        $this->assertEquals(2, $order['amount']);
        $this->assertEquals(80, $order['totalPrice']);
    }

    public function testAddOrderAction(): void
    {
        $cartFixtures = new CartFixtures();
        $this->loadFixture($cartFixtures);

        $payload = [
            'recipientName' => 'Vo Tinh Thanh',
            'recipientEmail' => 'votinhthanh.dev@gmail.com',
            'recipientPhone' => '0939456886',
            'addressDelivery' => 'NFQ Can Tho, Xuan Khanh, Ninh Kieu, CT'
        ];

        $this->client->request(
            Request::METHOD_POST,
            'api/users/orders',
            [$payload],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $order = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($order);
        $this->assertSame('Vo Tinh Thanh', $order['recipientName']);
        $this->assertSame('votinhthanh.dev@gmail.com', $order['recipientEmail']);
        $this->assertSame('0939456886', $order['recipientPhone']);
        $this->assertSame('NFQ Can Tho, Xuan Khanh, Ninh Kieu, CT', $order['addressDelivery']);
        $this->assertSame('Pending', $order['status']);
        $this->assertCount(2, $order['items']);
        $this->assertEquals(2, $order['amount']);
        $this->assertEquals(800000, $order['totalPrice']);
    }

    public function testCancelPurchaseOrderAction(): void
    {
        $purchaseOrder = new PurchaseOrderFixtures();
        $this->loadFixture($purchaseOrder);

        $user = $this->userRepository->findOneBy(['email' => 'user@gmail.com']);
        $order = $this->purchaseOrderRepository->findOneBy(['customer' => $user->getId()]);

        $this->client->request(
            Request::METHOD_DELETE,
            'api/users/orders/' . $order->getId(),
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        $order = $this->purchaseOrderRepository->findOneBy(['customer' => $user->getId()]);
        $this->assertSame('3', $order->getStatus());
    }

    public function testGetOrderAction(): void
    {
        $purchaseOrder = new PurchaseOrderFixtures();
        $this->loadFixture($purchaseOrder);

        $user = $this->userRepository->findOneBy(['email' => 'user@gmail.com']);
        $order = $this->purchaseOrderRepository->findOneBy(['customer' => $user->getId()]);

        $this->client->request(
            Request::METHOD_GET,
            'api/users/orders/' . $order->getId(),
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $order = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($order);
        $this->assertSame('Recipient Name', $order['recipientName']);
        $this->assertSame('Recipient Email', $order['recipientEmail']);
        $this->assertSame('0123456789', $order['recipientPhone']);
        $this->assertSame('Cai Khe, Ninh Kieu', $order['addressDelivery']);
        $this->assertEquals(2, $order['amount']);
        $this->assertEquals(80, $order['totalPrice']);
    }
}
