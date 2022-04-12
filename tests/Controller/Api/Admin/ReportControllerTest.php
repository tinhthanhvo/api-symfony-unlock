<?php

namespace App\Tests\Controller\Api\Admin;

use App\DataFixtures\UserFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testExportCsvPurchaseOrderInfo(): void
    {
        $userFixtures = new UserFixtures();
        $this->loadFixture($userFixtures);

        $payload = [
            'fileName' => 'purchase-order-export',
            'status' => 2,
            'fromDate' => '2022-04-10',
            'toDate' => '2022-04-10'
        ];
        $this->client->request(
            Request::METHOD_POST,
            '/api/admin/orders/export/csv',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('http://127.0.0.1:8080/purchase-order-export.csv', $data['success']);
        // Clear file after test
        $filesystem = new Filesystem();
        $filesystem->remove('purchase-order-export.csv');
    }

    public function testExportCsvProductInfo(): void
    {
        $userFixtures = new UserFixtures();
        $this->loadFixture($userFixtures);

        $payload = [
            'fileName' => 'product-export',
            'fromDate' => '2022-04-10',
            'toDate' => '2022-04-10'
        ];
        $this->client->request(
            Request::METHOD_POST,
            '/api/admin/products/export/csv',
            [],
            [],
            [
                'HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE,
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('http://127.0.0.1:8080/product-export.csv', $data['success']);
        // Clear file after test
        $filesystem = new Filesystem();
        $filesystem->remove('product-export.csv');
    }
}
