<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\PurchaseOrderFixtures;
use App\Entity\PurchaseOrder;
use App\Entity\User;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $purchaseOrderRepository;
    private $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->purchaseOrderRepository = $this->entityManager->getRepository(PurchaseOrder::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testExportCustomerInvoice(): void
    {
        $purchaseFixtures = new PurchaseOrderFixtures();
        $this->loadFixture($purchaseFixtures);

        $user = $this->userRepository->findOneBy(['email' => 'user@gmail.com']);
        $order = $this->purchaseOrderRepository->findOneBy([
            'customer' => $user->getId(),
            'status' => '4'
        ]);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/orders/' . $order->getId() . '/export',
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
        // Clear file after test
        $filename = str_replace('http://127.0.0.1:8080/', '', $data['success']);
        $filesystem = new Filesystem();
        $filesystem->remove($filename);
    }
}
