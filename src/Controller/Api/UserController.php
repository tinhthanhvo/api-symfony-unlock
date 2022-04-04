<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GetUserInfo;
use App\Service\HandleDataOutput;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\RestBundle\Controller\Annotations as Rest;

class UserController extends AbstractFOSRestController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var User|null
     */
    private $userLoginInfo;
    /**
     * @var HandleDataOutput
     */
    private $handleDataOutput;

    /**
     * @param UserRepository $userRepository
     */
    public function __construct(
        UserRepository $userRepository,
        GetUserInfo $userLogin,
        HandleDataOutput $handleDataOutput
    )
    {
        $this->userRepository = $userRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->handleDataOutput = $handleDataOutput;
    }

    /**
     * @Rest\Post ("/users/email")
     * @param Request $request
     * @return Response
     */
    public function getUserByEmailAction(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $email = $requestData['email'];

        $user = $this->userRepository->findOneBy(['email' => $email, 'deleteAt' => null]);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($user, 'json', SerializationContext::create()->setGroups(array('getDetailUser')));
        $user = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($user, Response::HTTP_OK));
    }

    /**
    * @Rest\Get ("/users/profile")
    * @return Response
    */
    public function getUserLogin(): Response
    {
        $user = $this->handleDataOutput->transferDataGroup([$this->userLoginInfo], 'getDetailUser');

        return $this->handleView($this->view($user[0], Response::HTTP_OK));
    }
}
