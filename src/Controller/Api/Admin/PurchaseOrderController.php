<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use App\Entity\OrderDetail;
use App\Entity\PurchaseOrder;
use App\Event\PurchaseOrderEvent;
use App\Repository\ProductItemRepository;
use App\Repository\PurchaseOrderRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class PurchaseOrderController extends BaseController
{
    public const PRODUCT_PER_PAGE = 10;
    public const PRODUCT_PAGE_NUMBER = 1;

    /**
     * @Rest\Get("/orders")
     * @return Response
     * @throws \Exception
     */
    public function getPurchaseOrdersAction(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PER_PAGE);
        $page = $request->get('page', self::PRODUCT_PAGE_NUMBER);
        $filterByStatus = $request->get('status', BaseController::STATUS_DEFAULT_NULL);
        $offset = $limit * ($page - 1);

        $today = new \DateTime("now");
        $fromDateRequest = $request->get('fromDate', '1900-01-01');
        $fromDate = new \DateTime($fromDateRequest);
        $toDateRequest = $request->get('toDate', $today) . ' 23:59:59.999999';
        $toDate = new \DateTime($toDateRequest);

        if ($fromDate > $toDate || $fromDate > $today) {
            return $this->handleView($this->view(['error' => 'Request is unsuccessful.'], Response::HTTP_BAD_REQUEST));
        }
        $purchaseOrders = $this->purchaseOrderRepository->findByConditions(['fromDate' => $fromDate, 'toDate' => $toDate, 'status' => $filterByStatus], ['status' => 'ASC'], $limit, $offset);
        $purchaseOrders['data'] = array_map('self::dataTransferOrderObject', $purchaseOrders['data']);
        return $this->handleView($this->view($purchaseOrders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/summary")
     * @return Response
     * @throws \Exception
     */
    public function getSummary(Request $request): Response
    {
        $today = new \DateTime("now");
        $today = $today->format('Y-m-d');
        $fromDateRequest = $request->get('dateRequest', $today);
        $fromDate = new \DateTime($fromDateRequest);
        $toDate = new \DateTime($fromDateRequest . ' 23:59:59.999999');

        $revenue = $this->purchaseOrderRepository->getReport('totalPrice', $fromDate, $toDate);
        $summery['amountOrder'] = $this->purchaseOrderRepository->getCountPurchaseOrder(self::STATUS_DEFAULT_NULL, $fromDate, $toDate);
        $summery['totalShippingCost'] = $this->purchaseOrderRepository->getReport('shippingCost', $fromDate, $toDate);
        $summery['revenue'] = $revenue - $summery['totalShippingCost'];
        $summery['totalItem'] = $this->purchaseOrderRepository->getReport('totalItem', $fromDate, $toDate);
        $summery['amountPendingOrder'] = $this->purchaseOrderRepository->getCountPurchaseOrder(self::STATUS_PENDING, $fromDate, $toDate);
        $summery['amountApprovedOrder'] = $this->purchaseOrderRepository->getCountPurchaseOrder(self::STATUS_APPROVED, $fromDate, $toDate);
        $summery['amountCanceledOrder'] = $this->purchaseOrderRepository->getCountPurchaseOrder(self::STATUS_CANCELED, $fromDate, $toDate);
        $summery['amountCompletedOrder'] = $this->purchaseOrderRepository->getCountPurchaseOrder(self::STATUS_COMPLETED, $fromDate, $toDate);

        return $this->handleView($this->view($summery, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/chart")
     * @return Response
     * @throws Exception
     */
    public function getDataToChart(): Response
    {
        $dataChart = $this->purchaseOrderRepository->reportDataCompletedOrders();

        return $this->handleView($this->view($dataChart, Response::HTTP_OK));
    }

    /**
     * @Rest\Put("/orders/{id}")
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateStatusPurchaseOrderAction(int $id, Request $request): Response
    {
        $purchaseOrder = $this->purchaseOrderRepository->find($id);

        if (!$purchaseOrder) {
            return $this->handleView($this->view(
                ['error' => 'Order is not found.'],
                Response::HTTP_NOT_FOUND
            ));
        }
        $status = $request->get('status');
        $previousStatus = $purchaseOrder->getStatus();

        if (count(self::checkUpdatingOrderCondition($purchaseOrder, $status)) > 0) {
            return $this->handleView($this->view(
                self::checkUpdatingOrderCondition($purchaseOrder, $status),
                Response::HTTP_BAD_REQUEST
            ));
        }

        if ($status == 3) {
            self::cancelPurchaseOrderAction($purchaseOrder);
        }

        $purchaseOrder->setStatus($status);
        $purchaseOrder->setUpdateAt(new \DateTime('now'));

        $this->purchaseOrderRepository->add($purchaseOrder);

        $event = new PurchaseOrderEvent($purchaseOrder, $previousStatus);
        $this->eventDispatcher->dispatch($event);

        $purchaseOrder = self::dataTransferOrderObject($purchaseOrder);

        return $this->handleView($this->view($purchaseOrder, Response::HTTP_OK));
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

            if ($status == BaseController::STATUS_PENDING) {
                $purchaseOrder->setStatus(BaseController::STATUS_CANCELED);
                $purchaseOrder->setUpdateAt(new \DateTime());

                $items = $purchaseOrder->getOrderItems();
                foreach ($items as $item) {
                    $productItem = $item->getProductItem();
                    $productItem->setAmount($productItem->getAmount() + $item->getAmount());
                    $productItem->setUpdateAt(new \DateTime());

                    $this->productItemRepository->add($productItem);
                }

                $this->purchaseOrderRepository->add($purchaseOrder);

                $event = new PurchaseOrderEvent($purchaseOrder);
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

    /**
     * @param PurchaseOrder $purchaseOrder
     * @param int $status
     * @return array|string[]
     */
    private function checkUpdatingOrderCondition(PurchaseOrder $purchaseOrder, int $status): array
    {
        $previousStatus = $purchaseOrder->getStatus();
        if ($status == $previousStatus) {
            return ['messageError' => 'The new status same with the current status.'];
        }

        if ($previousStatus == BaseController::STATUS_CANCELED) {
            return ['messageError' => 'The purchase order was canceled.'];
        }

        if ($status < $previousStatus) {
            return ['messageError' => 'Unable to return to previous status.'];
        }
        return [];
    }
}
