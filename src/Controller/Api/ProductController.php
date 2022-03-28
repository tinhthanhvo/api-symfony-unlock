<?php

namespace App\Controller\Api;

use App\Entity\Color;
use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Repository\ProductRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractFOSRestController
{
    private $productRepository;
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Rest\Get("/products")
     */
    public function getProducts(): Response
    {
        $products = $this->productRepository->findAll();

        $products = array_map('self::dataTransferProductObject', $products);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($products, 'json', SerializationContext::create()->setGroups(array('getProductList')));
        $products = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($products));
    }

    /**
     * @Rest\Get("/products/{id}")
     * @param int $id
     */
    public function getProduct(int $id): Response
    {
        $product = $this->productRepository->find($id);
        if(!$product) {
            $view = $this->view(['error' => 'Product is not found.'], Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }

        $product = $this->dataTransferProductObject($product);

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($product, 'json', SerializationContext::create()->setGroups(array('show')));
        $product = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    /**
    * @param Product $product
    * @return array
    */
    public function dataTransferProductObject(Product $product): array
    {
        $formattedProduct = [];
        $formattedProduct['id'] = $product->getId();
        $formattedProduct['name'] = $product->getName();
        $formattedProduct['description'] = $product->getDescription();
        $formattedProduct['price'] = $product->getPrice();

        $gallery = $product->getGallery();
        foreach ($gallery as $image) {
            $formattedProduct['gallery'][] =  $image->getPath();
        }

        $formattedProduct['color'] = $product->getColor()->getName();

        $items = $product->getItems();
        foreach ($items as $item) {
            $formattedProduct['items'][] =  $this->dataTransferItemObject($item);
        }

        return $formattedProduct;
    }

    /**
     * @param ProductItem $productItem
     * @return array
     */
    public function dataTransferItemObject(ProductItem $productItem) : array
    {
        $item = [];
        $item['id'] = $productItem->getId();
        $item['amount'] = $productItem->getAmount();
        $item['size'] = $productItem->getSize()->getValue();
        return $item;
    }
}
