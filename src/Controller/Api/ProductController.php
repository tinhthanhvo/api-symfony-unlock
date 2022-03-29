<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ProductItem;
use App\Repository\ProductRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $products = $this->productRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC'], 8);
        if ($products) {
            $transferData = array_map('self::dataTransferProductListObject', $products);
            $products = $this->transferDataGroup($transferData, 'getProductList');

            return $this->handleView($this->view($products, Response::HTTP_OK));
        }

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    /**
     * @Rest\Get("/products/{id}")
     * @param int $id
     */
    public function getProduct(int $id): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            $view = $this->view(['error' => 'Product is not found.'], Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }

        $product = $this->dataTransferProductObject($product);
        $product = $this->transferDataGroup($product, 'getDetailProduct');

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/products/filter")
     * @param Request $request
     * @return Response
     */
    public function getProductListFilter(Request $request): Response
    {
        $dataFilter = json_decode($request->getContent(), true);
        $products = $this->productRepository->findByOptions($dataFilter);

        $transferData = array_map('self::dataTransferProductListObject', $products);
        $products = $this->transferDataGroup($transferData, 'getProductList');

        return $this->handleView($this->view($products, Response::HTTP_OK));
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
     * @param Product $product
     * @return array
     */
    public function dataTransferProductListObject(Product $product): array
    {
        $formattedProduct = [];
        $formattedProduct['id'] = $product->getId();
        $formattedProduct['name'] = $product->getName();
        $formattedProduct['price'] = $product->getPrice();
        foreach ($product->getGallery() as $gallery) {
            $formattedProduct['gallery'][] = $gallery->getPath();
        }

        return $formattedProduct;
    }


    /**
     * @param ProductItem $productItem
     * @return array
     */
    public function dataTransferItemObject(ProductItem $productItem): array
    {
        $item = [];
        $item['id'] = $productItem->getId();
        $item['amount'] = $productItem->getAmount();
        $item['size'] = $productItem->getSize()->getValue();
        return $item;
    }

    /**
     * @param array $data
     * @param string $group
     * @return array
     */
    private function transferDataGroup(array $data, string $group): array
    {
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $data,
            'json',
            SerializationContext::create()->setGroups(array($group))
        );

        return $serializer->deserialize($convertToJson, 'array', 'json');
    }
}
