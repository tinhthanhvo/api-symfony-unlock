<?php

namespace App\Controller\Api\Admin;

use App\Repository\ProductRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractFOSRestController
{
    public const PRODUCT_PER_PAGE = 10;
    public const PRODUCT_PAGE_NUMBER = 1;
    public const PATH = '127.0.0.1/uploads/images/';
    private $productRepository;

    public function __construct(
        ProductRepository $productRepository
    )
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Rest\Get("/products")
     * @return Response
     */
    public function getProductsAction(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $products = $this->productRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC'], $limit);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $products,
            'json',
            SerializationContext::create()->setGroups(array('getProductListAdmin'))
        );
        $products = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($products, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/products/{id}")
     * @param int $id
     * @return Response
     */
    public function getProductAction(int $id): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->handleView($this->view(
                ['error' => 'Product is not found.'],
                Response::HTTP_NOT_FOUND
            ));
        }

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $product,
            'json',
            SerializationContext::create()->setGroups(array('getDetailProductAdmin'))
        );
        $product = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }
}
