<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Repository\CartRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractFOSRestController
{
    public const CART_ITEMS_PER_PAGE = 10;
    public const CART_ITEMS_PAGE_NUMBER = 1;

    private $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    /**
     * @Rest\Get("/carts")
     * @param Request $request
     * @return Response
     */
    public function getCarts(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        if (isset($payload['userId'])) {
            $limit = intval($request->get('limit', self::CART_ITEMS_PER_PAGE));
            $page = intval($request->get('page', self::CART_ITEMS_PAGE_NUMBER));
            $offset = $limit * ($page - 1);
            $carts = $this->cartRepository->findBy(
                ['deleteAt' => null, 'user' => $payload['userId']],
                ['createAt' => 'DESC'],
                $limit,
                $offset
            );

            $transferData = array_map('self::dataTransferCartItemObject', $carts);
            $carts = $this->transferDataGroup($transferData, 'getCartItems');

            return $this->handleView($this->view(['success' => $carts], Response::HTTP_OK));
        }

        return $this->handleView($this->view(['error' => 'Please provide user id'], Response::HTTP_NOT_FOUND));
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
        $formattedCart['price'] = $cart->getPrice();

        $gallery = $cart->getProductItem()->getProduct()->getGallery();
        foreach ($gallery as $image) {
            $formattedCart['gallery'][] =  $image->getPath();
        }

        return $formattedCart;
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
