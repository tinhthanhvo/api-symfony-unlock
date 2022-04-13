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
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseController extends AbstractFOSRestController
{
    protected const ORDER_BY_DEFAULT = ['id' => 'DESC'];
    protected const CONDITION_DEFAULT = ['deleteAt' => null];
    protected const ITEMS_PAGE_NUMBER_DEFAULT = 1;
    protected const DEFAULT_NULL = 0;
    protected const STATUS_DEFAULT_NULL = 0;
    protected const STATUS_PENDING = 1;
    protected const STATUS_APPROVED = 2;
    protected const STATUS_CANCELED = 3;
    protected const STATUS_COMPLETED = 4;

    /** @var integer HTTP status code - 200 (OK) by default */
    protected $statusCode = 200;

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

    /** @var SizeRepository */
    protected $sizeRepository;

    /** @var ProductItemRepository */
    protected $productItemRepository;

    /** @var GalleryRepository */
    protected $galleryRepository;

    /** @var EventDispatcherInterface */
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
     * Gets the value of statusCode.
     * @return integer
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets the value of statusCode.
     * @param integer $statusCode the status code
     * @return self
     */
    protected function setStatusCode(int $statusCode): BaseController
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Returns a JSON response
     * @param array $data
     * @param array $headers
     * @return JsonResponse
     */
    public function response(array $data, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    /**
     * Sets an error message and returns a JSON response
     * @param string $errors
     * @param array $headers
     * @return JsonResponse
     */
    public function respondWithErrors(string $errors, array $headers = []): JsonResponse
    {
        $data = [
            'status' => $this->getStatusCode(),
            'errors' => $errors,
        ];

        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    /**
     * Sets an error message and returns a JSON response
     * @param string $success
     * @param array $headers
     * @return JsonResponse
     */
    public function respondWithSuccess(string $success, array $headers = []): JsonResponse
    {
        $data = [
            'status' => $this->getStatusCode(),
            'success' => $success,
        ];

        return new JsonResponse($data, $this->getStatusCode(), $headers);
    }

    /**
     * Returns a 401 Unauthorized http response
     * @param string $message
     * @return JsonResponse
     */
    public function respondUnauthorized(string $message = 'Not authorized!'): JsonResponse
    {
        return $this->setStatusCode(401)->respondWithErrors($message);
    }

    /**
     * Returns a 422 Unprocessable Entity
     * @param string $message
     * @return JsonResponse
     */
    public function respondValidationError(string $message = 'Validation errors'): JsonResponse
    {
        return $this->setStatusCode(422)->respondWithErrors($message);
    }

    /**
     * Returns a 404 Not Found
     * @param string $message
     * @return JsonResponse
     */
    public function respondNotFound(string $message = 'Not found!'): JsonResponse
    {
        return $this->setStatusCode(404)->respondWithErrors($message);
    }

    /**
     * Returns a 201 Created
     * @param array $data
     * @return JsonResponse
     */
    public function respondCreated(array $data = []): JsonResponse
    {
        return $this->setStatusCode(201)->response($data);
    }

    /**
     * This method allows us to accept JSON payloads in POST requests
     * Since Symfony 4 doesn’t handle that automatically:
     * @param Request $request
     * @return Request
     */
    protected function transformJsonBody(Request $request): Request
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $request;
        }
        $request->request->replace($data);

        return $request;
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
