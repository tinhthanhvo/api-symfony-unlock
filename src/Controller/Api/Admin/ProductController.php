<?php

namespace App\Controller\Api\Admin;

use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Repository\ProductItemRepository;
use App\Repository\ProductRepository;
use App\Repository\SizeRepository;
use App\Service\FileUploader;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ProductController extends AbstractFOSRestController
{
    public const PRODUCT_PER_PAGE = 10;
    public const PRODUCT_PAGE_NUMBER = 1;
    public const PATH = '127.0.0.1:8080/uploads/images/';
    private $productRepository;
    private $sizeRepository;
    /**
     * @var ProductItemRepository
     */
    private $productItemRepository;

    public function __construct(
        ProductRepository $productRepository,
        SizeRepository $sizeRepository,
        ProductItemRepository $productItemRepository
    ) {
        $this->productRepository = $productRepository;
        $this->sizeRepository = $sizeRepository;
        $this->productItemRepository = $productItemRepository;
    }

    /**
     * @Rest\Get("/products")
     * @return Response
     */
    public function getProductsAction(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::PRODUCT_PAGE_NUMBER);
        $offset = $limit * ($page - 1);
        $products = $this->productRepository->findByConditions(['deleteAt' => null], ['createAt' => 'DESC'], $limit, $offset);

        $transferData = array_map('self::dataTransferObject', $products['data']);
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $transferData,
            'json',
            SerializationContext::create()->setGroups(array('getProductListAdmin'))
        );
        $products['data'] = $serializer->deserialize($convertToJson, 'array', 'json');

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

        $dataTransfer = self::dataTransferProductItemObject($product);
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $dataTransfer,
            'json',
            SerializationContext::create()->setGroups(array('getDetailProductAdmin'))
        );
        $dataResponse = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($dataResponse, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/products")
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function insertProductAction(Request $request, FileUploader $fileUploader): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $requestData = $request->request->all();

        $existedProduct = $this->productRepository->findOneBy([
            'name' => $requestData['name']
        ]);
        if ($existedProduct && $existedProduct->getColor()->getId() == $requestData['color']) {
            return $this->handleView($this->view(['error' => 'This product is existed.'], Response::HTTP_BAD_REQUEST));
        }

        $form->submit($request->request->all());
        if ($form->isSubmitted()) {
            $product->setCreateAt(new \DateTime());

            $galleryData = $request->files->get('gallery');
            foreach ($galleryData as $image) {
                $saveFile = $fileUploader->upload($image);
                $saveFile = self::PATH . $saveFile;
                $gallery = new Gallery();
                $gallery->setCreateAt(new \DateTime());
                $gallery->setPath($saveFile);
                $product->addGallery($gallery);
            }

            $productItemsData = (json_decode($requestData['items'][0], true));
            foreach ($productItemsData as $productItemData) {
                $productItem = new ProductItem();
                $productItem->setCreateAt(new \DateTime());
                $size = $this->sizeRepository->find($productItemData['size']);
                $productItem->setSize($size);
                $productItem->setProduct($product);
                $productItem->setAmount($productItemData['amount']);
                $product->addItem($productItem);
            }

            $this->productRepository->add($product);

            $serializer = SerializerBuilder::create()->build();
            $convertToJson = $serializer->serialize(
                $product,
                'json',
                SerializationContext::create()->setGroups(array('getDetailProductAdmin'))
            );
            $product = $serializer->deserialize($convertToJson, 'array', 'json');
            return $this->handleView($this->view($product, Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Put("/products/{id}")
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function updateProductAction(Product $product, Request $request): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $requestData = $request->request->all();
        $form->submit($request->request->all());
        if ($form->isSubmitted()) {
            $product->setUpdateAt(new \DateTime());
            $productItemsData = $requestData['items'];

            foreach ($productItemsData as $productItemData) {
                $productItem = $this->productItemRepository->find($productItemData['id']);
                $productItem->setAmount($productItemData['amount']);
                $this->productItemRepository->add($productItem);
                $product->addItem($productItem);
            }
            $this->productRepository->add($product);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @param Product $product
     * @return array
     */
    private function dataTransferObject(Product $product): array
    {
        $formattedProduct = [];
        $formattedProduct['id'] = $product->getId();
        $formattedProduct['name'] = $product->getName();
        $formattedProduct['price'] = $product->getPrice();
        $formattedProduct['description'] = $product->getDescription();
        $formattedProduct['category'] = $product->getCategory();
        $formattedProduct['color'] = $product->getColor();

        $gallery = $product->getGallery();
        foreach ($gallery as $image) {
            $formattedProduct['gallery'][] = $image->getPath();
        }
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
    private function dataTransferProductItemObject(Product $product): array
    {
        $formattedProduct = [];
        $formattedProduct['id'] = $product->getId();
        $formattedProduct['name'] = $product->getName();
        $formattedProduct['price'] = $product->getPrice();
        $formattedProduct['description'] = $product->getDescription();
        $formattedProduct['category'] = $product->getCategory()->getName();
        $formattedProduct['color'] = $product->getColor()->getName();

        $gallery = $product->getGallery();
        foreach ($gallery as $image) {
            $formattedProduct['gallery'][] = $image->getPath();
        }
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
    private function dataTransferItemObject(ProductItem $productItem): array
    {
        $item = [];
        $item['id'] = $productItem->getId();
        $item['amount'] = $productItem->getAmount();
        $item['size'] = $productItem->getSize()->getValue();

        return $item;
    }
}
