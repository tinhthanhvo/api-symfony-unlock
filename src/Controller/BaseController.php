<?php

namespace App\Controller;

use App\Repository\CartRepository;
use App\Repository\CategoryRepository;
use App\Repository\ColorRepository;
use App\Repository\ProductRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\UserRepository;
use App\Service\ExportData;
use App\Service\GetUserInfo;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Form;

class BaseController extends AbstractFOSRestController
{
    protected const ORDER_BY_DEFAULT = ['createAt' => 'DESC'];
    protected const CONDITION_DEFAULT = ['deleteAt' => null];
    protected const ITEMS_PAGE_NUMBER_DEFAULT = 1;

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

    public function __construct(
        CartRepository $cartRepository,
        CategoryRepository $categoryRepository,
        ColorRepository $colorRepository,
        ProductRepository $productRepository,
        PurchaseOrderRepository $purchaseOrderRepository,
        UserRepository $userRepository,
        ExportData $exportData,
        LoggerInterface $logger,
        GetUserInfo $userLogin
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
