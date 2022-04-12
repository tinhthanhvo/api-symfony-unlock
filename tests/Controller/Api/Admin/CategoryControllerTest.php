<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends BaseWebTestCase
{
    public function testGetCategoriesAction(): void
    {
        $cagoryFixtures = new CategoryFixtures();
        $this->loadFixture($cagoryFixtures);

        $userFixtures = new UserFixtures();
        $this->loadFixture($userFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/admin/categories',
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
    }
}
