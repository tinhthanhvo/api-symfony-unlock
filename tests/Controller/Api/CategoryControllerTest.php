<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CategoryFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function setUp(): void
    {
        parent::setUp();
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
            ['HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        $category = $data[0];
        $this->assertSame('Category name', $category['name']);
    }
}
