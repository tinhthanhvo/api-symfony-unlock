<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
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
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Rest\Post ("/users/profile")
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
}
