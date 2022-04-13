<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\PurchaseOrder;
use App\Entity\User;
use App\Event\PurchaseOrderEvent;
use App\Service\MailerService;
use FOS\RestBundle\Controller\Annotations as Rest;
use Monolog\Handler\SendGridHandler;
use SendGrid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridApiTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class HomePageController extends BaseController
{
    public const PRODUCT_PER_PAGE = 9;

    /**
     * @Rest\Get("/categories")
     * @return Response
     */
    public function getCategories(): ?Response
    {
        $categories = $this->categoryRepository->findBy(
            self::CONDITION_DEFAULT,
            ['name' => 'ASC']
        );
        $categories = $this->transferDataGroup($categories, 'getListCategory');

        return $this->handleView($this->view($categories, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/colors")
     * @return Response
     */
    public function getColors(): ?Response
    {
        $colors = $this->colorRepository->findBy(
            self::CONDITION_DEFAULT,
            ['name' => 'ASC']
        );
        $colors = $this->transferDataGroup($colors, 'getColorList');

        return $this->handleView($this->view($colors, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/products")
     * @param Request $request
     * @return Response
     */
    public function getProducts(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $products = $this->productRepository->findBy(
            self::CONDITION_DEFAULT,
            self::ORDER_BY_DEFAULT,
            $limit
        );
        $transferData = array_map('self::dataTransferProductListObject', $products);
        $products = $this->transferDataGroup($transferData, 'getProductList');

        return $this->handleView($this->view($products, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/products/{id}")
     * @param int $id
     * @return Response
     */
    public function getProduct(int $id): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->handleView($this->view(
                ['error' => 'Product is not found.'],
                Response::HTTP_NOT_FOUND
            ));
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
        $filterOptions = (json_decode($request->getContent(), true)) ?? [];
        $orderBy = ($filterOptions['order']) ?? self::ORDER_BY_DEFAULT;

        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::ITEMS_PAGE_NUMBER_DEFAULT);
        $offset = $limit * ($page - 1);

        $products = $this->productRepository->findByConditions($filterOptions, $orderBy, $limit, $offset);

        $transferData = array_map('self::dataTransferProductListObject', $products['data']);
        $products['data'] = $this->transferDataGroup($transferData, 'getProductList');

        return $this->handleView($this->view($products, Response::HTTP_OK));
    }

    /**
     * @param Product $product
     * @return array
     */
    private function dataTransferProductObject(Product $product): array
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
    private function dataTransferProductListObject(Product $product): array
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
    private function dataTransferItemObject(ProductItem $productItem): array
    {
        $item = [];
        $item['id'] = $productItem->getId();
        $item['amount'] = $productItem->getAmount();

        $item['amountInCart'] = 0;
        if ($this->userLoginInfo) {
            $cartItems = $this->cartRepository->findOneBy([
                'deleteAt' => null,
                'user' => $this->userLoginInfo->getId(),
                'productItem' => $productItem->getId()
            ]);

            if ($cartItems) {
                $item['amountInCart'] = $cartItems->getAmount();
            }
        }

        $item['size'] = $productItem->getSize()->getValue();

        return $item;
    }

    /**
     * @Rest\Get("/email")
     * @return Response
     */
    public function sendMail(): Response
    {
        $purchaseOrder = new PurchaseOrder(new User());
        $event = new PurchaseOrderEvent($purchaseOrder);
        $this->eventDispatcher->dispatch($event);

        return $this->handleView($this->view(['success' => 'Send mail successfully.']));
    }
}
