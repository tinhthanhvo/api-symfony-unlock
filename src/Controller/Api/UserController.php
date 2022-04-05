<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends BaseController
{
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
        $userInfo = $this->transferDataGroup([$user], 'getDetailUser');

        return $this->handleView($this->view($userInfo[0], Response::HTTP_OK));
    }

    /**
    * @Rest\Get ("/users/profile")
    * @return Response
    */
    public function getUserLogin(): Response
    {
        $user = $this->transferDataGroup([$this->userLoginInfo], 'getDetailUser');

        return $this->handleView($this->view($user[0], Response::HTTP_OK));
    }
}
