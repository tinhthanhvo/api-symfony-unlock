<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Form\UserPasswordType;
use App\Form\UserProfileType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Require ROLE_USER for all the actions of this controller
 * @IsGranted("ROLE_USER")
 */
class UserController extends BaseController
{
    /**
     * @Rest\Post("/users/email")
     * @param Request $request
     * @return Response
     */
    public function getUserByEmailAction(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['email' => $requestData['email'], 'deleteAt' => null]);
        $userInfo = $this->transferDataGroup([$user], 'getDetailUser');

        return $this->handleView($this->view($userInfo[0], Response::HTTP_OK));
    }

    /**
    * @Rest\Get("/users/profile")
    * @return Response
    */
    public function getUserLogin(): Response
    {
        $user = $this->transferDataGroup([$this->userLoginInfo], 'getDetailUser');

        return $this->handleView($this->view($user[0], Response::HTTP_OK));
    }

    /**
     * @Rest\Put("/users/profile")
     * @param Request $request
     * @return Response
     */
    public function updateUserLoginInfo(Request $request): Response
    {
        try {
            $user = $this->userLoginInfo;
            $form = $this->createForm(UserProfileType::class, $user);
            $form->submit(json_decode($request->getContent(), true));
            if ($form->isSubmitted() && $form->isValid()) {
                $user->setUpdateAt(new \DateTime("now"));
                $user->setDeleteAt(null);
                $this->userRepository->add($user);

                return $this->handleView($this->view(
                    ['success' => 'Update profile successfully.'],
                    Response::HTTP_NO_CONTENT
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
     * @Rest\Put("/users/password")
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function updateUserPassword(Request $request, UserPasswordHasherInterface $encoder): Response
    {
        try {
            $form = $this->createForm(UserPasswordType::class, []);
            $payload = json_decode($request->getContent(), true);
            $form->submit($payload);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->userLoginInfo;
                $user->setPassword($encoder->hashPassword($user, $payload['newPassword']));
                $user->setUpdateAt(new \DateTime("now"));
                $user->setDeleteAt(null);
                $this->userRepository->add($user);

                return $this->handleView($this->view(
                    ['success' => 'Update password successfully.'],
                    Response::HTTP_NO_CONTENT
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
}
