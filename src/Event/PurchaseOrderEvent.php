<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\PurchaseOrder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UserEvent.
 */
class PurchaseOrderEvent extends Event
{
    const TEMPLATE_CONTACT = "email/invoice.html.twig";
    /**
     * @var PurchaseOrder
     */
    public $order;

    /**
     * @param PurchaseOrder $order
     */
    public function __construct(PurchaseOrder $order)
    {
        $this->order = $order;
    }

    /**
     * @return PurchaseOrder
     */
    public function getPurchaseOrder(): PurchaseOrder
    {
        return $this->order;
    }
}
