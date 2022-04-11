<?php

namespace App\EventSubscriber;

use App\Event\OrderEvent;
use App\Event\PurchaseOrderEvent;
use App\Service\MailerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailerService
     */
    protected $mailerService;

    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    public function onSendOrderDetailToUser(PurchaseOrderEvent $event)
    {
        $data = $event->getPurchaseOrder();
        $order = $data['order'];
        $previousStatus = $data['previousStatus'];
        $status = $order->getStatus();
        $withRole = $data['withRole'];

        $params = [
            "order" => $order,
            "previousStatus" => $previousStatus
        ];

        if($status == PurchaseOrderEvent::STATUS_PENDING) {
            $this->mailerService->send(
                'Confirm order information',
                'tinhthanh2210@gmail.com',
                $order->getRecipientEmail(),
                PurchaseOrderEvent::TEMPLATE_CONFIRM,
                $params
            );
        }

        if($status == PurchaseOrderEvent::STATUS_CANCELED && $withRole == PurchaseOrderEvent::ROLE_DEFAULT) {
            $this->mailerService->send(
                'Cancel Your Order',
                'tinhthanh2210@gmail.com',
                $order->getRecipientEmail(),
                PurchaseOrderEvent::TEMPLATE_CANCEL,
                $params
            );
        }
        elseif ($status != $previousStatus && $withRole == PurchaseOrderEvent::ROLE_DEFAULT) {
            $this->mailerService->send(
                'Update Status Your Order',
                'tinhthanh2210@gmail.com',
                $order->getRecipientEmail(),
                PurchaseOrderEvent::TEMPLATE_UPDATE_STATUS,
                $params
            );
        }
    }

    public function onSendOrderDetailToAdmin(PurchaseOrderEvent $event)
    {
        $data = $event->getPurchaseOrder();
        $order = $data['order'];
        $previousStatus = $data['previousStatus'];
        $status = $order->getStatus();

        $params = [
            "order" => $order,
            "previousStatus" => $previousStatus
        ];

        if($status == PurchaseOrderEvent::STATUS_PENDING) {
            $this->mailerService->send(
                'Confirm order information',
                'tinhthanh2210@gmail.com',
                'nguyenthilananh0606@gmail.com',
                PurchaseOrderEvent::TEMPLATE_CONFIRM,
                $params
            );
        }

        if ($status != $previousStatus) {
            $this->mailerService->send(
                'Update Status To Order',
                'tinhthanh2210@gmail.com',
                'nguyenthilananh0606@gmail.com',
                PurchaseOrderEvent::TEMPLATE_UPDATE_STATUS,
                $params
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return[
            PurchaseOrderEvent::class => [
                ['onSendOrderDetailToUser', 1],
                ['onSendOrderDetailToAdmin', 1]
            ]
        ];
    }
}
