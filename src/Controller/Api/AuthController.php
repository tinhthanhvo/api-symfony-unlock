<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Entity\User;
use App\Form\UserRegisterType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends BaseController
{
    /**
     * @Rest\Post("/email_check")
     * @param Request $request
     * @return Response
     */
    public function checkIfEmailIsExisted(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['email' => $payload['email']]);
        $isExisted = true;
        if (!$user) {
            $isExisted = false;
        }

        return $this->handleView($this->view(['isExisted' => $isExisted], Response::HTTP_OK));
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
                $this->userRepository->add($user);

                return $this->handleView($this->view(
                    ['success' => 'Insert user successfully.'],
                    Response::HTTP_CREATED
                ));
            }

            return $this->handleView($this->view(
                ['error' => $this->getFormErrorMessage($form)],
                Response::HTTP_BAD_REQUEST
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @Rest\Post("/login_check")
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function getTokenUser(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }
}
