<?php

namespace App\Controller\Api\Admin;

use App\Repository\CategoryRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
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
    public function getCategoriesAction(): Response
    {
        $categories = $this->categoryRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC']);
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $categories,
            'json',
            SerializationContext::create()->setGroups(array('getListCategory'))
        );
        $transferData = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($categories, Response::HTTP_OK));
    }
}
