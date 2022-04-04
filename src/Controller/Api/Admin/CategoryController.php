<?php

namespace App\Controller\Api\Admin;

use App\Repository\CategoryRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class CategoryController extends AbstractFOSRestController
{
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Rest\Get("/categories")
     * @return Response
     */
    public function getColorsAction(): Response
    {
        $categories = $this->categoryRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC']);

        return $this->handleView($this->view($categories, Response::HTTP_OK));
    }

}
