<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Entity\OrderDetail;
use App\Entity\PurchaseOrder;
use App\Event\PurchaseOrderEvent;
use App\Form\PurchaseOrderType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
* @IsGranted("ROLE_USER")
*/
class PurchaseOrderController extends BaseController
{
    public const PRODUCT_PER_PAGE = 10;
    public const PRODUCT_PAGE_NUMBER = 1;

    /**
    * @Rest\Get("/users/orders")
    * @return Response
    */
    public function getOrdersAction(Request $request): Response
    {
        $userId = $this->userLoginInfo->getId();
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::PRODUCT_PAGE_NUMBER);
        $offset = $limit * ($page - 1);
        $filterByStatus = $request->get('status', BaseController::STATUS_DEFAULT_NULL);
        $orders = $this->purchaseOrderRepository->findByConditions(['deleteAt' => null, 'customer' => $userId, 'status' => $filterByStatus], ['status' => 'ASC', 'id' => 'DESC'], $limit, $offset);
        $orders['data'] = array_map('self::dataTransferOrderObject', $orders['data']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/users/orders/{id}")
     * @return Response
     */
    public function getOrderAction(PurchaseOrder $purchaseOrder): Response
    {
        $transferPurchaseOrder = self::dataTransferOrderObject($purchaseOrder);

        return $this->handleView($this->view($transferPurchaseOrder, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/users/orders")
     * @return Response
     */
    public function addOrderAction(Request $request): Response
    {
        $order = new PurchaseOrder($this->userLoginInfo);
        $form = $this->createForm(PurchaseOrderType::class, $order);
        $requestData = json_decode($request->getContent(), true);
        $form->submit($requestData);

        $totalPrice = 0;
        $totalAmount = 0;
        if ($form->isSubmitted()) {
            $cartItemsData = $this->userLoginInfo->getCarts();
            $amountItemCart = count($cartItemsData);
            if ($amountItemCart == 0) {
                return $this->handleView($this->view(['error' => 'Nothing in cart!'], Response::HTTP_BAD_REQUEST));
            }
            foreach ($cartItemsData as $cartItemData) {
                $productItem = $cartItemData->getProductItem();
                $amount = intval($cartItemData->getAmount());

                if ($amount > $productItem->getAmount()) {
                    return $this->handleView($this->view(['error' => 'Amount of available product is not enough!'], Response::HTTP_BAD_REQUEST));
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
            $totalPrice += $requestData['shippingCost'];
            $order->setTotalPrice($totalPrice);
            $order->setAmount($totalAmount);

            $this->purchaseOrderRepository->add($order);

            if ($amountItemCart == count($order->getOrderItems())) {
                foreach ($cartItemsData as $cartItemData) {
                    $this->cartRepository->remove($cartItemData);
                }
            }
            $transferPurchaseOrder = self::dataTransferOrderObject($order);

            $event = new PurchaseOrderEvent($order);
            $this->eventDispatcher->dispatch($event);

            return $this->handleView($this->view($transferPurchaseOrder, Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Delete("/users/orders/{id}")
     * @param PurchaseOrder $purchaseOrder
     * @return void
     */
    public function cancelPurchaseOrderAction(PurchaseOrder $purchaseOrder, Request $request): Response
    {
        try {
            $status = $purchaseOrder->getStatus();
            $data = json_decode($request->getContent(), true);

            if ($status == BaseController::STATUS_PENDING) {
                $purchaseOrder->setStatus(BaseController::STATUS_CANCELED);
                $purchaseOrder->setUpdateAt(new \DateTime());
                if(isset($data['reason'])) {
                    $purchaseOrder->setCanceledReason($data['reason']);
                    $purchaseOrder->setUserCancel($this->userLoginInfo);
                }

                $items = $purchaseOrder->getOrderItems();
                foreach ($items as $item) {
                    $productItem = $item->getProductItem();
                    $productItem->setAmount($productItem->getAmount() + $item->getAmount());
                    $productItem->setUpdateAt(new \DateTime());

                    $this->productItemRepository->add($productItem);
                }
                $this->purchaseOrderRepository->add($purchaseOrder);

                $event = new PurchaseOrderEvent($purchaseOrder, BaseController::STATUS_PENDING, "USER");
                $this->eventDispatcher->dispatch($event);

                return $this->handleView($this->view(['success' => 'Order is canceled!'], Response::HTTP_NO_CONTENT));
            }

            return $this->handleView($this->view([
                'error' => 'This order is approved. So, your request is failed.'
            ], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            //write to log
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * @Rest\get("/users/orders/{id}/repurchase")
     * @return Response
     */
    public function repurchase(int $id): Response
    {
        try {
            $user = $this->userLoginInfo;
            $order = $this->purchaseOrderRepository->findOneBy(['id' => $id, 'customer' => $user->getId()]);
            if($order->getStatus() != BaseController::STATUS_COMPLETED && $order->getStatus() != BaseController::STATUS_CANCELED) {
                return $this->handleView($this->view(
                    ['error' => 'This order is in process.'],
                    Response::HTTP_BAD_REQUEST
                ));
            }

            $orderDetail = $order->getOrderItems();
            $countItemsAddCart = 0;

            foreach ($orderDetail as $item) {
                $recordCart = [
                    'productItem' => $item->getProductItem()->getId(),
                    'amount' => $item->getAmount(),
                    'price' => $item->getProductItem()->getProduct()->getPrice(),
                ];

                $check = $this->cartService->addCart($recordCart);
                if ($check)
                    $countItemsAddCart += 1;
            }
            if ($countItemsAddCart == 0) {
                return $this->handleView($this->view(['error' => 'Can not add product to cart'], Response::HTTP_BAD_REQUEST));
            }

            return $this->handleView($this->view(['success' => 'Add ' . $countItemsAddCart . ' items to cart'], Response::HTTP_CREATED));

        } catch (\Exception $e) {
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    private function dataTransferOrderObject(PurchaseOrder $purchaseOrder): array
    {
        $formattedPurchaseOrder = [];
        $formattedPurchaseOrder['id'] = $purchaseOrder->getId();
        $formattedPurchaseOrder['createAt'] = $purchaseOrder->getCreateAt()->format('d-m-Y');
        $formattedPurchaseOrder['recipientName'] = $purchaseOrder->getRecipientName();
        $formattedPurchaseOrder['recipientEmail'] = $purchaseOrder->getRecipientEmail();
        $formattedPurchaseOrder['recipientPhone'] = $purchaseOrder->getRecipientPhone();
        $formattedPurchaseOrder['addressDelivery'] = $purchaseOrder->getAddressDelivery();
        $formattedPurchaseOrder['status'] = self::formattedStatusOrderResponse($purchaseOrder->getStatus());
        $formattedPurchaseOrder['amount'] = $purchaseOrder->getAmount();
        $formattedPurchaseOrder['shippingCost'] = $purchaseOrder->getShippingCost();
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice();
        $formattedPurchaseOrder['$canceledReason'] = $purchaseOrder->getCanceledReason();

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

        $item['gallery'] = "";
        $gallery = $orderDetail->getProductItem()->getProduct()->getGallery();
        if (count($gallery) > 0) {
            $item['gallery'] = $gallery[0]->getPath();
        }

        return $item;
    }

    /**
     * @param string $status
     * @return string
     */
    private function formattedStatusOrderResponse(string $status): string
    {
        $statusResponse = 'Pending';
        switch ($status) {
            case '2':
                $statusResponse = 'Approved';
                break;
            case '3':
                $statusResponse = 'Canceled';
                break;
            case '4':
                $statusResponse = 'Completed';
                break;
        }

        return $statusResponse;
    }
}
