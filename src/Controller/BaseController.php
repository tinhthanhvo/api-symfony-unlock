<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use App\Repository\ColorRepository;
use App\Repository\GalleryRepository;
use App\Repository\ProductItemRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\SizeRepository;
use App\Repository\UserRepository;
use App\Service\ExportData;
use App\Service\GetUserInfo;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Form;

class BaseController extends AbstractFOSRestController
{
    protected const ORDER_BY_DEFAULT = ['id' => 'DESC'];
    protected const CONDITION_DEFAULT = ['deleteAt' => null];
    protected const ITEMS_PAGE_NUMBER_DEFAULT = 1;
    public const DEFAULT_NULL = 0;
    public const STATUS_DEFAULT_NULL = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;

    /** @var CartRepository */
    protected $cartRepository;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var ColorRepository */
    protected $colorRepository;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var PurchaseOrderRepository */
    protected $purchaseOrderRepository;

    /** @var UserRepository */
    protected $userRepository;

    protected $exportData;

    /** @var LoggerInterface */
    protected $logger;

    /** @var User|null */
    protected $userLoginInfo;
    /**
     * @var SizeRepository
     */
    protected $sizeRepository;
    /**
     * @var ProductItemRepository
     */
    protected $productItemRepository;
    /**
     * @var GalleryRepository
     */
    protected $galleryRepository;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        CartRepository $cartRepository,
        CategoryRepository $categoryRepository,
        ColorRepository $colorRepository,
        ProductRepository $productRepository,
        PurchaseOrderRepository $purchaseOrderRepository,
        UserRepository $userRepository,
        ExportData $exportData,
        LoggerInterface $logger,
        GetUserInfo $userLogin,
        SizeRepository $sizeRepository,
        ProductItemRepository $productItemRepository,
        GalleryRepository $galleryRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->cartRepository = $cartRepository;
        $this->categoryRepository = $categoryRepository;
        $this->colorRepository = $colorRepository;
        $this->productRepository = $productRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->userRepository = $userRepository;
        $this->exportData = $exportData;
        $this->logger = $logger;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->sizeRepository = $sizeRepository;
        $this->productItemRepository = $productItemRepository;
        $this->galleryRepository = $galleryRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param array $data
     * @param string $group
     * @return array
     */
    protected function transferDataGroup(array $data, string $group): array
    {
        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize(
            $data,
            'json',
            SerializationContext::create()->setGroups([$group])
        );

        return $serializer->deserialize($convertToJson, 'array', 'json');
    }

    /**
     * @param Form $form
     * @return array
     */
    protected function getFormErrorMessage(Form $form): array
    {
        $errorMessage = [];

        foreach ($form as $child) {
            /** @var FormInterface $child */
            if ($child->isSubmitted() && $child->isValid()) {
                continue;
            }

            $errorList = $child->getErrors(true, true);
            if (0 === count($errorList)) {
                continue;
            } else {
                $firstErrorMessage = '';
                foreach ($errorList as $error) {
                    $firstErrorMessage = $error->getMessage();
                    break;
                }

                $errorMessage[$child->getName()] = $firstErrorMessage;
            }
        }

        return $errorMessage;
    }
}
