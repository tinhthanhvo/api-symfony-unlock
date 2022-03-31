<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Response;

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
    public function getCategories(): ?Response
    {
        $categories = $this->categoryRepository->findBy(['deleteAt' => null], ['name' => 'ASC']);
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('getListCategory')));
        $categories = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($categories, Response::HTTP_OK));
    }
}
