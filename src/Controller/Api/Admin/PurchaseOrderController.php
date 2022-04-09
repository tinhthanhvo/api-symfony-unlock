<?php

namespace App\Controller\Api\Admin;

use App\Entity\OrderDetail;
use App\Entity\PurchaseOrder;
use App\Repository\PurchaseOrderRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class PurchaseOrderController extends AbstractFOSRestController
{
    public const PRODUCT_PER_PAGE = 10;
    public const PRODUCT_PAGE_NUMBER = 1;
    private $purchaseOrderRepository;

    public function __construct(
        PurchaseOrderRepository $purchaseOrderRepository
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * @Rest\Get("/orders")
     * @return Response
     */
    public function getPurchaseOrdersAction(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::PRODUCT_PAGE_NUMBER);
        $offset = $limit * ($page - 1);

        $purchaseOrders = $this->purchaseOrderRepository->findByConditions([], ['status' => 'ASC'], $limit, $offset);
        $purchaseOrders['data'] = array_map('self::dataTransferOrderObject', $purchaseOrders['data']);
        return $this->handleView($this->view($purchaseOrders, Response::HTTP_OK));
    }


    /**
     * @Rest\Put("/orders/{id}")
     * @return Response
     */
    public function updateStatusPurchaseOrderAction(PurchaseOrder $purchaseOrder, Request $request): Response
    {
        $status = $request->get('status');

        if ($status != $purchaseOrder->getStatus()) {
            $purchaseOrder->setStatus($status);
            $purchaseOrder->setUpdateAt(new \DateTime('now'));
        }

        $this->purchaseOrderRepository->add($purchaseOrder);

        $purchaseOrder = self::dataTransferOrderObject($purchaseOrder);

        return $this->handleView($this->view($purchaseOrder, Response::HTTP_OK));
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
