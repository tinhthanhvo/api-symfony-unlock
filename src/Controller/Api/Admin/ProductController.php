<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Service\FileUploader;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ProductController extends BaseController
{
    private const PRODUCT_PER_PAGE = 10;
    private const AMOUNT_IMAGE_REQUIRE = 5;
    private const PATH = 'http://127.0.0.1:8080/uploads/images/';

    /**
     * @Rest\Get("/products")
     * @param Request $request
     * @return Response
     */
    public function getProductsAction(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::ITEMS_PAGE_NUMBER_DEFAULT);
        $offset = $limit * ($page - 1);
        $products = $this->productRepository->findByConditions(
            self::CONDITION_DEFAULT,
            self::ORDER_BY_DEFAULT,
            $limit,
            $offset
        );

        $transferData = array_map('self::dataTransferObject', $products['data']);
        $products['data'] = $this->transferDataGroup($transferData, 'getProductListAdmin');

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

        $transferData = self::dataTransferProductItemObject($product);
        $product = $this->transferDataGroup($transferData, 'getDetailProductAdmin');

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/products/filter")
     * @param Request $request
     * @return Response
     */
    public function getProductListFilter(Request $request): Response
    {
        $filterOptions = (json_decode($request->getContent(), true)) ?? [];
        $orderBy = ($filterOptions['order']) ?? self::ORDER_BY_DEFAULT;

        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::ITEMS_PAGE_NUMBER_DEFAULT);
        $offset = $limit * ($page - 1);

        $products = $this->productRepository->findByConditions($filterOptions, $orderBy, $limit, $offset);

        $transferData = array_map('self::dataTransferObject', $products['data']);
        $products['data'] = $this->transferDataGroup($transferData, 'getProductListAdmin');

        return $this->handleView($this->view($products, Response::HTTP_OK));
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

        if (
            $existedProduct &&
            $existedProduct->getColor()->getId() == $requestData['color'] &&
            $existedProduct->getCategory()->getId() == $requestData['category']
        ) {
            return $this->handleView($this->view([
                'error' => 'This product is existed.'
            ], Response::HTTP_BAD_REQUEST));
        }

        $form->submit($requestData);
        if ($form->isSubmitted()) {
            $galleryData = $request->files->get('gallery');
            if (is_null($galleryData) || count($galleryData) != self::AMOUNT_IMAGE_REQUIRE) {
                return $this->handleView($this->view([
                    'error' => 'You must choose five images to upload for product.'
                ], Response::HTTP_BAD_REQUEST));
            }
            foreach ($galleryData as $image) {
                $saveFile = $fileUploader->upload($image);
                $saveFile = self::PATH . $saveFile;
                $gallery = new Gallery();
                $gallery->setPath($saveFile);
                $product->addGallery($gallery);
            }

            $productItemsData = json_decode($requestData['items'][0], true);
            foreach ($productItemsData as $productItemData) {
                if ($productItemData['amount'] < 0) {
                    return $this->handleView($this->view([
                        'error' => 'Amount items must be unsigned integer.'
                    ], Response::HTTP_BAD_REQUEST));
                }

                $productItem = new ProductItem();
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
     * @Rest\Post("/products/{id}")
     * @param int $id
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function updateProductAction(int $id, Request $request, FileUploader $fileUploader): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->handleView($this->view(
                ['error' => 'Product is not found.'],
                Response::HTTP_NOT_FOUND
            ));
        }
        $form = $this->createForm(ProductUpdateType::class, $product);
        $requestData = $request->request->all();
        $existedProducts = $this->productRepository->findBy([
            'name' => $requestData['name']
        ]);

        foreach ($existedProducts as $existedProduct) {
            if ($existedProduct->getId() != $product->getId()) {
                return $this->handleView($this->view(
                    ['error' => 'This name is already used.'],
                    Response::HTTP_BAD_REQUEST
                ));
            }
        }
        $form->submit($request->request->all());
        if ($form->isSubmitted()) {
            $productItemsData = json_decode($requestData['items'][0], true);
            foreach ($productItemsData as $productItemData) {
                if ($productItemData['amount'] < 0) {
                    return $this->handleView($this->view([
                        'error' => 'Amount items must be unsigned integer.'
                    ], Response::HTTP_BAD_REQUEST));
                }
                $productItem = $this->productItemRepository->find($productItemData['id']);
                $productItem->setAmount($productItemData['amount']);
                $this->productItemRepository->add($productItem);
                $product->addItem($productItem);
            }

            $galleryData = $request->files->get('gallery');
            if ($galleryData != null) {
                if (count($galleryData) != self::AMOUNT_IMAGE_REQUIRE) {
                    return $this->handleView($this->view([
                        'error' => 'You must choose five images to upload for product.'
                    ], Response::HTTP_BAD_REQUEST));
                }
                $gallery = $product->getGallery();
                foreach ($galleryData as $i => $image) {
                    $saveFile = $fileUploader->upload($image);
                    $pathImage = self::PATH . $saveFile;
                    $gallery[$i]->setPath($pathImage);
                    $this->galleryRepository->add($gallery[$i]);
                    $product->addGallery($gallery[$i]);
                }
            }

            $product->setUpdateAt(new \DateTime());
            $this->productRepository->add($product);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Delete("products/{id}")
     * @param int $id
     * @return Response
     */
    public function deleteProduct(int $id): Response
    {
        try {
            $product = $this->productRepository->find($id);
            if (!$product) {
                return $this->handleView($this->view(
                    ['error' => 'This product is not existed.'],
                    Response::HTTP_NOT_FOUND
                ));
            }

            $productItems = $product->getItems();
            foreach ($productItems as $productItem) {
                if (!self::deleteItem($productItem)) {
                    self::rollbackDeleteItems($productItems);
                    return $this->handleView($this->view(
                        ['error' => 'Deleted product is unsuccessful.'],
                        Response::HTTP_BAD_REQUEST
                    ));
                }
            }

            $product->setDeleteAt(new \DateTime());
            $this->productRepository->add($product);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @param ProductItem $item
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function deleteItem(ProductItem $item): bool
    {
        $item->setDeleteAt(new \DateTime());
        $this->productItemRepository->add($item);

        return true;
    }

    /**
     * @param array $items
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function rollbackDeleteItems(array $items): void
    {
        foreach ($items as $item) {
            $item->setDeleteAt(null);
            $this->productItemRepository->add($item);
        }
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
        $formattedProduct['createAt'] = $product->getCreateAt()->format('d-m-Y');
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
