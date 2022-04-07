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

    public function onSendOrder(PurchaseOrderEvent $event)
    {
        $order = $event->getPurchaseOrder();

        $params = [
            "order" => $order
        ];

        $this->mailerService->send(
            'Confirm order information',
            'tinhthanh2210@gmail.com',
            $order->getRecipientEmail(),
            PurchaseOrderEvent::TEMPLATE_CONTACT,
            $params
        );
    }
    public static function getSubscribedEvents()
    {
        return[
            PurchaseOrderEvent::class => [
                ['onSendOrder', 1]
            ]
        ];
    }
}