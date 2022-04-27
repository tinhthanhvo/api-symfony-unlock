<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Entity\Cart;
use App\Form\CartItemType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;

/**
 * Require ROLE_USER for all the actions of this controller
 * @IsGranted("ROLE_USER")
 */
class CartController extends BaseController
{
    public const CART_ITEMS_PER_PAGE = 10;

    /**
     * @Rest\Get("/users/carts/count")
     * @return Response
     */
    public function countCartItems(): Response
    {
        $cartItems = $this->cartRepository->findBy(['user' => $this->userLoginInfo->getId()]);

        return $this->handleView($this->view(['count' => count($cartItems)], Response::HTTP_OK));
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
            $page = intval($request->get('page', self::ITEMS_PAGE_NUMBER_DEFAULT));
            $offset = $limit * ($page - 1);
            $cartItems = $this->cartRepository->findBy(
                ['deleteAt' => null, 'user' => $this->userLoginInfo->getId()],
                self::ORDER_BY_DEFAULT,
                $limit,
                $offset
            );

            $transferData = array_map('self::dataTransferCartItemObject', $cartItems);
            $cartItems = $this->transferDataGroup($transferData, 'getCartItems');

            return $this->handleView($this->view($cartItems, Response::HTTP_OK));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
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
        $formattedCart['productItem'] = $cart->getProductItem()->getId();
        $formattedCart['name'] = $cart->getProductItem()->getProduct()->getName();
        $formattedCart['color'] = $cart->getProductItem()->getProduct()->getColor()->getName();
        $formattedCart['size'] = $cart->getProductItem()->getSize()->getValue();
        $formattedCart['amount'] = $cart->getAmount();
        $formattedCart['totalAmount'] = $cart->getProductItem()->getAmount();
        $formattedCart['price'] = $cart->getPrice();
        $formattedCart['unitPrice'] = $cart->getProductItem()->getProduct()->getPrice();

        $formattedCart['gallery'] = '';
        $gallery = $cart->getProductItem()->getProduct()->getGallery();
        if (count($gallery) > 0) {
            $formattedCart['gallery'] = $gallery[0]->getPath();
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

            $productItem = $this->productItemRepository->find($payload['productItem']);
            $amountInStock = $productItem->getAmount();
            $payload['amount'] = ($payload['amount'] >= $amountInStock) ? $amountInStock : $payload['amount'];

            $cartItem = $this->cartRepository->findOneBy([
                'productItem' => $payload['productItem'],
                'user' => $this->userLoginInfo->getId()
            ]);

            if (!$cartItem) {
                $cartItem = new Cart();
                $cartItem->setUser($this->userLoginInfo);
            } else {
                $cartItem->setUpdateAt(new \DateTime("now"));
            }

            $form = $this->createForm(CartItemType::class, $cartItem);
            $form->submit($payload);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->cartRepository->add($cartItem);

                return $this->handleView($this->view(
                    ['success' => 'Insert cart item successfully.'],
                    Response::HTTP_CREATED
                ));
            }

            return $this->handleView($this->view(
                ['error' => $this->getFormErrorMessage($form)],
                Response::HTTP_BAD_REQUEST
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
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
                    $this->cartRepository->add($cartItem);

                    return $this->handleView($this->view(
                        ['success' => 'Update cart item successfully.'],
                        Response::HTTP_NO_CONTENT
                    ));
                }

                return $this->handleView($this->view(
                    ['error' => $this->getFormErrorMessage($form)],
                    Response::HTTP_BAD_REQUEST
                ));
            }

            return $this->handleView($this->view(
                ['error' => 'No item in cart was found with this id.'],
                Response::HTTP_NOT_FOUND
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
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

                return $this->handleView($this->view(
                    ['success' => 'Delete cart item successfully.'],
                    Response::HTTP_NO_CONTENT
                ));
            }

            return $this->handleView($this->view(
                ['error' => 'No item in cart was found with this id.'],
                Response::HTTP_NOT_FOUND
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }
}
