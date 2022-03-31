<?php

namespace App\Controller\Api;


use App\Controller\ApiController;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends ApiController
{

    /**
     * @Rest\Post ("/register")
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @param ManagerRegistry $registry
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordHasherInterface $encoder, ManagerRegistry $registry): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        $em = $registry->getManager();
        $password = $requestData['password'];
        $fullName = $requestData['fullName'];
        $email = $requestData['email'];

        if (empty($password) || empty($email)){
            return $this->respondValidationError("Invalid Username or Password or Email");
        }


        $user = new User();
        $user->setPassword($encoder->hashPassword($user, $password));
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setCreateAt(new \DateTime('now'));

        $em->persist($user);
        $em->flush();
        return $this->respondWithSuccess(sprintf('User %s successfully created', $user->getUserIdentifier()));
    }

    /**
     * @Rest\Get ("/login_check")
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function getTokenUser(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }

}