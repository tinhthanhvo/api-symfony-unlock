<?php

namespace App\Tests\Controller;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectManager;
use Exception;
use FOS\OAuthServerBundle\Model\ClientInterface;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use OAuth2\OAuth2;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BaseWebTestCase extends WebTestCase
{
    /**
     * Default content type.
     */
    protected const DEFAULT_MIME_TYPE = 'application/json';

    /**
     * @var KernelBrowser
     */
    protected $client;

    /**
     * @var string
     */
    protected static $token;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @return void
     * @throws JWTEncodeFailureException
     */
    public static function setUpBeforeClass(): void
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        /** @var  JWTEncoderInterface $jwtEncode */
        $jwtEncode = static::$kernel->getContainer()->get('lexik_jwt_authentication.encoder');
        self::$token = $jwtEncode->encode(['username' => 'user@gmail.com']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->client = self::createClient(array(), array(
            'HTTP_HOST' => '127.0.0.1',
        ));

        $this->entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function loadFixture(FixtureInterface $fixture)
    {
        $loader = new Loader();
        $loader->addFixture($fixture);
        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures(), true);
    }

    // Delete whole data of database on tearDown
    protected function tearDown(): void
    {
        parent::tearDown();

        // Purge all the fixtures data when the tests are finished
        $purger = new ORMPurger($this->entityManager);
        // Purger mode delete, delete all data in database test
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $purger->purge();
    }
}
