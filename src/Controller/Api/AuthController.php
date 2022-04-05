<?php

namespace App\Controller\Api;

use App\Controller\ApiController;
use App\Entity\User;
use App\Form\UserRegisterType;
use App\Repository\UserRepository;
use App\Service\HandleDataOutput;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends ApiController
{
    private $userRepository;
    private $handleDataOutput;

    public function __construct(
        UserRepository $userRepository,
        HandleDataOutput $handleDataOutput
    ) {
        $this->userRepository = $userRepository;
        $this->handleDataOutput = $handleDataOutput;
    }

    /**
    * @Rest\Post("/register")
    * @param Request $request
    * @param UserPasswordHasherInterface $encoder
    * @return Response
    */
    public function register(Request $request, UserPasswordHasherInterface $encoder): Response
    {
        try {
            $user = new User();
            $form = $this->createForm(UserRegisterType::class, $user);

            $payload = json_decode($request->getContent(), true);
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
            }

            $errorsMessage = $this->handleDataOutput->getFormErrorMessage($form);

            return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
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
