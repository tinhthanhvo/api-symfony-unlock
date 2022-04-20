<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require ROLE_ADMIN for all the actions of this controller
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

    /**
     * @Rest\Get("/categories/{id}")
     * @param int $id
     * @return Response
     */
    public function getCategoryAction(int $id): Response
    {
        $category = $this->categoryRepository->findOneBy(['id' => $id]);
        if (!$category) {
            return $this->handleView($this->view(
                ['error' => 'Category is not found.'],
                Response::HTTP_NOT_FOUND
            ));
        }
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($category, 'json', SerializationContext::create()->setGroups(array('getDetailCategory')));
        $category = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($category, Response::HTTP_OK));
    }

}
