<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    private $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testRegister(): void
    {
        $payload = [
            'email' => 'user_mail@gmail.com',
            'password' => '23abncH',
            'fullName' => 'User full name',
            'phoneNumber' => '0908855655'
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/register',
            [],
            [],
            ['HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $user = $this->userRepository->findOneBy(['email' => 'user_mail@gmail.com']);
        $this->assertNotEmpty($user);
        $this->assertSame('User full name', $user->getFullName());
    }

    public function testCheckIfEmailIsExisted(): void
    {
        $userFixtures = new UserFixtures();
        $this->loadFixture($userFixtures);

        $payload = ['email' => 'user@gmail.com'];
        $this->client->request(
            Request::METHOD_POST,
            '/api/email_check',
            [],
            [],
            ['HTTP_ACCEPT' => self::DEFAULT_MIME_TYPE],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(true, $data['isExisted']);
    }
}
