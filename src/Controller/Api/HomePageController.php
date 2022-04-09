<?php

namespace App\Controller\Api;

use App\Entity\Color;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\PurchaseOrder;
use App\Entity\User;
use App\Event\PurchaseOrderEvent;
use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use App\Repository\ColorRepository;
use App\Repository\ProductRepository;
use App\Service\GetUserInfo;
use App\Service\MailerService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Monolog\Handler\SendGridHandler;
use SendGrid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridApiTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class HomePageController extends AbstractFOSRestController
{
    public const PRODUCT_PER_PAGE = 9;
    public const PRODUCT_PAGE_NUMBER = 1;
    public const ORDER_BY_DEFAULT = ['id' => 'DESC'];
    const CONDITION_DEFAULT = ['deleteAt' => null];

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var CartRepository */
    private $cartRepository;

    private $eventDispatcher;

    /** @var ProductRepository */
    private $productRepository;

    /** @var User|null */
    private $userLoginInfo;
    /**
     * @var ColorRepository
     */
    private $colorRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        ColorRepository $colorRepository,
        CartRepository $cartRepository,
        EventDispatcherInterface $eventDispatcher,
        GetUserInfo $userLogin,
        ProductRepository $productRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->cartRepository = $cartRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->productRepository = $productRepository;
        $this->colorRepository = $colorRepository;
    }

    /**
     * @Rest\Get("/categories")
     * @return Response
     */
    public function getCategories(): ?Response
    {
        $categories = $this->categoryRepository->findBy(['deleteAt' => null], ['name' => 'ASC']);
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
        $products = $this->productRepository->findBy(['deleteAt' => null], self::ORDER_BY_DEFAULT, $limit);
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

        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::PRODUCT_PAGE_NUMBER);
        $orderBy = $request->get('order', self::ORDER_BY_DEFAULT);
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

    /**
     * @Rest\Get ("/email")
     * @return Response
     */
    public function sendMail(): Response
    {
        $purchaseOrder = new PurchaseOrder(new User(), 0);
        $event = new PurchaseOrderEvent($purchaseOrder);
        $this->eventDispatcher->dispatch($event);

        return $this->handleView($this->view(['success' => 'Send mail successfully.']));
    }
}
