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
    const TEMPLATE_CONFIRM = "email/invoice.html.twig";
    const TEMPLATE_UPDATE_STATUS = "email/change_status.html.twig";
    const TEMPLATE_CANCEL = "email/cancel.html.twig";
    public const STATUS_APPROVED = 1;
    public const STATUS_DELIVERY = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;
    public const ROLE_DEFAULT = "ADMIN";
    /**
     * @var PurchaseOrder
     */
    public $order;

    /**
     * @var int
     */
    public $previousStatus;

    /**
     * @var string
     */
    public $withRole;

    /**
     * @param PurchaseOrder $order
     */
    public function __construct(
        PurchaseOrder $order,
        int $previousStatus = self::STATUS_APPROVED,
        string $withRole = self::ROLE_DEFAULT
    ) {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->withRole = $withRole;
    }

    /**
     * @return array
     */
    public function getPurchaseOrder(): array
    {
        return [
            'order' => $this->order,
            'previousStatus' => $this->previousStatus,
            'withRole' => $this->withRole
        ];
    }
}
