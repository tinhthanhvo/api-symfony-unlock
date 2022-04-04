<?php

namespace App\Controller\Api;

use App\Controller\ApiController;
use App\Entity\User;
use App\Form\UserRegisterType;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends ApiController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Rest\Post("/register")
     * @param Request $request
     * @return Response
     */
    public function register(Request $request, UserPasswordHasherInterface $encoder): Response
    {
        $payload = json_decode($request->getContent(), true);

        $user = new User();
        $form = $this->createForm(UserRegisterType::class, $user);

        $form->submit($payload);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($encoder->hashPassword($user, $payload['password']));
            $user->setRoles(['ROLE_USER']);
            $user->setCreateAt(new \DateTime("now"));

            $this->userRepository->add($user);
            if (!empty($user->getId())) {
                return $this->handleView($this->view(
                    ['success' => 'Insert user successfully'],
                    Response::HTTP_CREATED
                ));
            }

            return $this->handleView($this->view(
                ['error' => 'Something went wrong! Please contact support.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }

        $errorsMessage = [];
        foreach ($form->getErrors(true, true) as $error) {
            $paramError = explode('=', $error->getMessage());
            $errorsMessage[$paramError[0]] = $paramError[1];
        }

        return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Post ("/login_check")
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function getTokenUser(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }
}
