<?php

namespace App\Controller\Api;

use App\Entity\OrderDetail;
use App\Entity\ProductItem;
use App\Entity\PurchaseOrder;
use App\Form\PurchaseOrderType;
use App\Repository\CartRepository;
use App\Repository\ProductItemRepository;
use App\Repository\PurchaseOrderRepository;
use App\Service\GetUserInfo;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
* @IsGranted("ROLE_USER")
*/
class PurchaseOrderController extends AbstractFOSRestController
{
    private $purchaseOrderRepository;
    private $productItemRepository;
    private $userLoginInfo;
    private $cartRepository;

    public function __construct(
        PurchaseOrderRepository $purchaseOrderRepository,
        GetUserInfo $userLogin,
        ProductItemRepository $productItemRepository,
        CartRepository $cartRepository
    )
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->productItemRepository = $productItemRepository;
        $this->cartRepository = $cartRepository;
    }

    /**
    * @Rest\Get("/users/orders")
    * @return Response
    */
    public function getOrdersAction(): Response
    {
        $orders = $this->purchaseOrderRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC']);
        $transferOrders = array_map('self::dataTransferObject', $orders);

        return $this->handleView($this->view($transferOrders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/users/orders/{id}")
     * @return Response
     */
    public function getOrderAction(PurchaseOrder $purchaseOrder): Response
    {
        $transferPurchaseOrder = self::dataTransferDetailOrderObject($purchaseOrder);

        return $this->handleView($this->view($transferPurchaseOrder, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/users/orders")
     * @return Response
     */
    public function addOrderAction(Request $request): Response
    {
        $order = new PurchaseOrder();
        $form = $this->createForm(PurchaseOrderType::class, $order);
        $requestData = $request->request->all();

        $form->submit($requestData);

        if ($form->isSubmitted()) {
            $order->setCreateAt(new \DateTime());
            $order->setStatus('Pending');
            $order->setCustomer($this->userLoginInfo);

            $totalPrice = 0;
            $totalAmount = 0;

            $cartItemsData = $this->userLoginInfo->getCarts();
            foreach ($cartItemsData as $cartItemData){

                $amount = $cartItemData->getAmount();
                $price = $cartItemData->getPrice() * $amount;

                $totalPrice += $price;
                $totalAmount += $amount;

                $orderDetail = new OrderDetail();
                $orderDetail->setCreateAt(new \DateTime());
                $orderDetail->setAmount($amount);
                $orderDetail->setPrice($price);

                $productItem = $cartItemData->getProductItem();
                if($amount > $productItem->getAmount()) {
                    return $this->handleView($this->view(['error' => 'Quantity is not enough.'], Response::HTTP_BAD_REQUEST));
                }
                $productItem->setAmount($productItem->getAmount() - $amount);
                $this->productItemRepository->add($productItem);

                $orderDetail->setProductItem($productItem);

                $order->addOrderItem($orderDetail);
                $order->setTotalPrice($totalPrice);
                $order->setAmount($totalAmount);
                $this->cartRepository->remove($cartItemData);
            }

            $this->purchaseOrderRepository->add($order);
            $transferPurchaseOrder = self::dataTransferDetailOrderObject($order);

            return $this->handleView($this->view($transferPurchaseOrder, Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Delete("/users/orders/{id}")
     * @param PurchaseOrder $purchaseOrder
     * @return void
     */
    public function cancelPurchaseOrderAction(PurchaseOrder $purchaseOrder): Response
    {
        try {
            $status = $purchaseOrder->getStatus();
            if ($status == 'Pending') {
                $purchaseOrder->setStatus('Canceled');
                $purchaseOrder->setUpdateAt(new \DateTime());
                $purchaseOrder->setDeleteAt(new \DateTime());

                $items = $purchaseOrder->getOrderItems();
                foreach ($items as $item) {
                    $productItem = $item->getProductItem();
                    $productItem->setAmount($productItem->getAmount() + $item->getAmount());
                    $productItem->setUpdateAt(new \DateTime());

                    $this->productItemRepository->add($productItem);
                }

                $this->purchaseOrderRepository->add($purchaseOrder);

                return $this->handleView($this->view(['success' => 'This order is canceled!'], Response::HTTP_NO_CONTENT));
            }

            return $this->handleView($this->view(['error' => 'This order is approved. So, your request is failed.'], Response::HTTP_BAD_REQUEST));
        }
        catch (\Exception $e) {
            //write to log
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ],Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    private function dataTransferObject(PurchaseOrder $purchaseOrder): array
    {
        $formattedPurchaseOrder = [];
        $formattedPurchaseOrder['id'] = $purchaseOrder->getId();
        $formattedPurchaseOrder['recipientName'] = $purchaseOrder->getRecipientName();
        $formattedPurchaseOrder['recipientEmail'] = $purchaseOrder->getRecipientEmail();
        $formattedPurchaseOrder['recipientPhone'] = $purchaseOrder->getRecipientPhone();
        $formattedPurchaseOrder['addressDelivery'] = $purchaseOrder->getAddressDelivery();
        $formattedPurchaseOrder['status'] = $purchaseOrder->getStatus();
        $formattedPurchaseOrder['amount'] = $purchaseOrder->getAmount();
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice();

        return $formattedPurchaseOrder;
    }

    private function dataTransferDetailOrderObject(PurchaseOrder $purchaseOrder): array
    {
        $formattedPurchaseOrder = [];
        $formattedPurchaseOrder['id'] = $purchaseOrder->getId();
        $formattedPurchaseOrder['recipientName'] = $purchaseOrder->getRecipientName();
        $formattedPurchaseOrder['recipientEmail'] = $purchaseOrder->getRecipientEmail();
        $formattedPurchaseOrder['recipientPhone'] = $purchaseOrder->getRecipientPhone();
        $formattedPurchaseOrder['addressDelivery'] = $purchaseOrder->getAddressDelivery();
        $formattedPurchaseOrder['status'] = $purchaseOrder->getStatus();
        $formattedPurchaseOrder['amount'] = $purchaseOrder->getAmount();
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice();

        $cartItems = $purchaseOrder->getOrderItems();
        foreach ($cartItems as $cartItem) {
            $formattedPurchaseOrder['items'][] =  self::dataTransferItemObject($cartItem);
        }

        return $formattedPurchaseOrder;
    }

    /**
     * @param OrderDetail OrderDetail $orderDetail
     * @return array
     */
    private function dataTransferItemObject(OrderDetail $orderDetail): array
    {
        $item = [];
        $productItem = $orderDetail->getProductItem();
        $item['id'] = $orderDetail->getId();
        $item['name'] = $productItem->getProduct()->getName();
        $item['color'] = $productItem->getProduct()->getColor()->getName();
        $item['size'] = $productItem->getSize()->getValue();
        $item['amount'] = $orderDetail->getAmount();
        $item['unitPrice'] = $productItem->getProduct()->getPrice();
        $item['price'] = $orderDetail->getPrice();

        return $item;
    }
}
