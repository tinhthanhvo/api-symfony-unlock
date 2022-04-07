<?php

namespace App\EventSubscriber;

use App\Event\PurchaseOrderEvent;
use App\Service\MailerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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

    /**
     * @throws TransportExceptionInterface
     */
    public function onSendOrder(PurchaseOrderEvent $event)
    {
        $order = $event->getPurchaseOrder();

        $this->mailerService->send(
            'Hello',
            'tinhthanh2210@gmail.com',
            'votinhthanh.dev@gmail.com',
            'Hello world'
        );
    }
    public static function getSubscribedEvents(): array
    {
        return[
            PurchaseOrderEvent::class => [
                ['onSendOrder', 1]
            ]
        ];
    }
}
