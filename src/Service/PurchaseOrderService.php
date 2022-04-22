<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\PurchaseOrder;
use App\Entity\OrderDetail;
use App\Entity\User;
use App\Form\CartItemType;
use App\Repository\CartRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\ProductItemRepository;
use Proxies\__CG__\App\Entity\ProductItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderService extends AbstractController
{
    public const METHOD_CAST = 'cast';
    public const METHOD_PAYPAL = 'paypal';

    /** @var User|null */
    private $userLoginInfo;


    private $productItemRepository;

    private $cartRepository;

    private $purchaseOrderRepository;

    public function __construct(
        GetUserInfo $userLogin,
        PurchaseOrderRepository $purchaseOrderRepository,
        ProductItemRepository $productItemRepository,
        CartRepository $cartRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->productItemRepository = $productItemRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->cartRepository = $cartRepository;
    }

    public function addOrder (PurchaseOrder $order, FormInterface $form, array $payload)
    {
        $form->submit($payload);
        $totalPrice = 0;
        $totalAmount = 0;
        try {
            if ($form->isSubmitted()) {
                $cartItemsData = $this->userLoginInfo->getCarts();

                if (count($cartItemsData) == 0) {
                    return ['error' => 'Your cart is empty.'];
                }
                foreach ($cartItemsData as $cartItemData) {
                    $productItem = $cartItemData->getProductItem();
                    $amount = intval($cartItemData->getAmount());

                    if ($amount > $productItem->getAmount()) {
                        return ['error' => 'Quantity is not enough.'];
                    }

                    $price = intval($productItem->getProduct()->getPrice()) * $amount;
                    $totalPrice += intval($price);
                    $totalAmount += $amount;
                    $orderDetail = new OrderDetail();
                    $orderDetail->setAmount($amount);
                    $orderDetail->setPrice($price);

                    $productItem->setAmount($productItem->getAmount() - $amount);
                    $this->productItemRepository->add($productItem);
                    $orderDetail->setProductItem($productItem);
                    $order->addOrderItem($orderDetail);
                }
                $totalPrice += $payload['shippingCost'];
                $order->setTotalPrice($totalPrice);
                $order->setAmount($totalAmount);
                $order->setUpdateAt(new \DateTime('now'));
                $this->purchaseOrderRepository->add($order);

//                if (count($cartItemsData) == count($order->getOrderItems())) {
//                    foreach ($cartItemsData as $cartItemData) {
//                        $this->cartRepository->remove($cartItemData);
//                    }
//                }

                return ['success' => 'Add order successfully.'];
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
