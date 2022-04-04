<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    /**
     * @return void
     */
    public function testGetUserByEmailAction(): void
    {
        $userFixture = new UserFixtures();
        $this->loadFixture($userFixture);

        $payload = [
            'email' => 'user@gmail.com'
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/users/profile',
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
        $this->assertSame(1, $data['id']);
        $this->assertSame('user@gmail.com', $data['email']);
        $this->assertSame('Full name', $data['full_name']);
        $this->assertSame('0123456789', $data['phone_number']);
    }
}
