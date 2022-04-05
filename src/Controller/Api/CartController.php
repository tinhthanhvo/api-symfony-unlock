<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Form\CartItemType;
use App\Repository\CartRepository;
use App\Service\GetUserInfo;
use App\Service\HandleDataOutput;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require ROLE_USER for all the actions of this controller
 * @IsGranted("ROLE_USER")
 */
class CartController extends AbstractFOSRestController
{
    public const CART_ITEMS_PER_PAGE = 10;
    public const CART_ITEMS_PAGE_NUMBER = 1;

    private $cartRepository;
    private $userLoginInfo;
    private $handleDataOutput;

    public function __construct(
        CartRepository $cartRepository,
        GetUserInfo $userLogin,
        HandleDataOutput $handleDataOutput
    ) {
        $this->cartRepository = $cartRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->handleDataOutput = $handleDataOutput;
    }

    /**
     * @Rest\Get("/users/carts/count")
     * @return Response
     */
    public function countCartItems(): Response
    {
        try {
            $countCartItems = $this->cartRepository->countCartItems($this->userLoginInfo->getId());

            return $this->handleView($this->view($countCartItems[0], Response::HTTP_OK));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @Rest\Get("/users/carts")
     * @param Request $request
     * @return Response
     */
    public function getCartItems(Request $request): Response
    {
        try {
            $limit = intval($request->get('limit', self::CART_ITEMS_PER_PAGE));
            $page = intval($request->get('page', self::CART_ITEMS_PAGE_NUMBER));
            $offset = $limit * ($page - 1);
            $carts = $this->cartRepository->findBy(
                ['deleteAt' => null, 'user' => $this->userLoginInfo->getId()],
                ['createAt' => 'DESC'],
                $limit,
                $offset
            );

            $transferData = array_map('self::dataTransferCartItemObject', $carts);
            $carts = $this->handleDataOutput->transferDataGroup($transferData, 'getCartItems');

            return $this->handleView($this->view($carts, Response::HTTP_OK));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @param Cart $cart
     * @return array
     */
    private function dataTransferCartItemObject(Cart $cart): array
    {
        $formattedCart = [];
        $formattedCart['id'] = $cart->getId();
        $formattedCart['name'] = $cart->getProductItem()->getProduct()->getName();
        $formattedCart['color'] = $cart->getProductItem()->getProduct()->getColor()->getName();
        $formattedCart['size'] = $cart->getProductItem()->getSize()->getValue();
        $formattedCart['amount'] = $cart->getAmount();
        $formattedCart['totalAmount'] = $cart->getProductItem()->getAmount();
        $formattedCart['price'] = $cart->getPrice();
        $formattedCart['unitPrice'] = $cart->getProductItem()->getProduct()->getPrice();

        $gallery = $cart->getProductItem()->getProduct()->getGallery();
        if (count($gallery) > 0) {
            $formattedCart['gallery'] = $gallery[0]->getPath();
        } else {
            $formattedCart['gallery'] = "";
        }

        return $formattedCart;
    }

    /**
     * @Rest\Post("/users/carts")
     * @param Request $request
     * @return Response
     */
    public function insertCartItem(Request $request): Response
    {
        try {
            $payload = json_decode($request->getContent(), true);
            $cartItem = $this->cartRepository->findOneBy([
                'productItem' => $payload['productItem'],
                'user' => $this->userLoginInfo->getId()
            ]);

            if (!$cartItem) {
                $cartItem = new Cart();
                $cartItem->setUser($this->userLoginInfo);
                $cartItem->setCreateAt(new \DateTime("now"));
            } else {
                $cartItem->setUpdateAt(new \DateTime("now"));
                $cartItem->setDeleteAt(null);

                $storageAmount = $cartItem->getProductItem()->getAmount();
                if (is_numeric($payload['amount']) && intval($payload['amount']) < $storageAmount) {
                    $totalCartAmount = $cartItem->getAmount() + intval($payload['amount']);
                    if ($storageAmount < $totalCartAmount) {
                        $payload['amount'] = $storageAmount;
                    } else {
                        $payload['amount'] = $totalCartAmount;
                    }
                }
            }

            $form = $this->createForm(CartItemType::class, $cartItem);
            $form->submit($payload);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->cartRepository->add($cartItem);

                return $this->handleView($this->view(
                    ['success' => 'Insert cart item successfully'],
                    Response::HTTP_CREATED
                ));
            }

            $errorsMessage = $this->handleDataOutput->getFormErrorMessage($form);

            return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @Rest\Put("/users/carts/{id}")
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function updateCartItem(int $id, Request $request): Response
    {
        try {
            $payload = json_decode($request->getContent(), true);
            $cartItem = $this->cartRepository->findOneBy([
                'id' => $id,
                'user' => $this->userLoginInfo->getId()
            ]);

            if ($cartItem) {
                $form = $this->createForm(CartItemType::class, $cartItem);
                $payload['productItem'] = $cartItem->getProductItem()->getId();
                $form->submit($payload);
                if ($form->isSubmitted() && $form->isValid()) {
                    $cartItem->setUpdateAt(new \DateTime("now"));
                    $cartItem->setDeleteAt(null);

                    $this->cartRepository->add($cartItem);

                    return $this->handleView($this->view(
                        ['success' => 'Update cart item successfully'],
                        Response::HTTP_NO_CONTENT
                    ));
                }

                $errorsMessage = $this->handleDataOutput->getFormErrorMessage($form);
            } else {
                $errorsMessage = ['id' => 'No item in cart was found with this id.'];
            }

            return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @Rest\Delete("/users/carts/{id}")
     * @param int $id
     * @return Response
     */
    public function removeCartItem(int $id): Response
    {
        try {
            $cartItem = $this->cartRepository->findOneBy([
                'id' => $id,
                'user' => $this->userLoginInfo->getId()
            ]);
            if ($cartItem) {
                $this->cartRepository->remove($cartItem);

                return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
            }

            return $this->handleView($this->view(
                ['error' => 'No item in cart was found with this id.'],
                Response::HTTP_NOT_FOUND
            ));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }
}
