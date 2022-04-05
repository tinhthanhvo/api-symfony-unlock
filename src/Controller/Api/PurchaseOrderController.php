<?php

namespace App\Controller\Api;

use App\Entity\OrderDetail;
use App\Entity\PurchaseOrder;
use App\Form\PurchaseOrderType;
use App\Repository\ProductItemRepository;
use App\Repository\PurchaseOrderRepository;
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

    public function __construct(
        PurchaseOrderRepository $purchaseOrderRepository,
        ProductItemRepository $productItemRepository
    )
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->productItemRepository = $productItemRepository;;
    }

    /**
    * @Rest\Get("/users/orders")
    * @return Response
    */
    public function getOrdersAction(): Response
    {
        $orders = $this->purchaseOrderRepository->findBy(['deleteAt' => null], ['createAt' => 'DESC']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
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

            $cartItemsData = (json_decode($requestData['items'][0], true));
            foreach ($cartItemsData as $cartItemData){
                $orderDetail = new OrderDetail();
                $orderDetail->setCreateAt(new \DateTime());
                $orderDetail->setAmount($cartItemData['amount']);
                $orderDetail->setPrice($cartItemData['price']);
                $productItem = $this->productItemRepository->find($cartItemData['productItem']);
                $productItem->addOrderDetail($orderDetail);
                $order->addOrderItem($orderDetail);
            }

            $this->purchaseOrderRepository->add($order);

            return $this->handleView($this->view($order, Response::HTTP_CREATED));
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
}
