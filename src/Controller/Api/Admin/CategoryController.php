<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class CategoryController extends BaseController
{
    /**
     * @Rest\Get("/categories")
     * @return Response
     */
    public function getCategoriesAction(): Response
    {
        $categories = $this->categoryRepository->findBy(
            self::CONDITION_DEFAULT,
            ['name' => 'ASC']
        );
        $categories = $this->transferDataGroup($categories, 'getListCategory');

        return $this->handleView($this->view($categories, Response::HTTP_OK));
    }
}
