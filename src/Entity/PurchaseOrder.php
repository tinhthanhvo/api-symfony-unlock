<?php

namespace App\Entity;

use App\Repository\PurchaseOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PurchaseOrderRepository::class)
 */
class PurchaseOrder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updateAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $paymentAt;

    /**
     * @ORM\Column(type="bigint")
     */
    private $totalPrice;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleteAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\OneToMany(targetEntity=OrderDetail::class, mappedBy="purchaseOrder", orphanRemoval=true, cascade={"persist"})
     */
    private $orderItems;

    /**
     * @ORM\Column(type="text")
     */
    private $addressDelivery;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $recipientName;

    /**
     * @ORM\Column(type="string", length=11)
     */
    private $recipientPhone;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $recipientEmail;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeInterface $createAt): self
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTimeInterface $updateAt): self
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getPaymentAt(): ?\DateTimeInterface
    {
        return $this->paymentAt;
    }

    public function setPaymentAt(?\DateTimeInterface $paymentAt): self
    {
        $this->paymentAt = $paymentAt;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getDeleteAt(): ?\DateTimeInterface
    {
        return $this->deleteAt;
    }

    public function setDeleteAt(?\DateTimeInterface $deleteAt): self
    {
        $this->deleteAt = $deleteAt;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetail>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderDetail $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setPurchaseOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderDetail $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getPurchaseOrder() === $this) {
                $orderItem->setPurchaseOrder(null);
            }
        }

        return $this;
    }

    public function getAddressDelivery(): ?string
    {
        return $this->addressDelivery;
    }

    public function setAddressDelivery(string $addressDelivery): self
    {
        $this->addressDelivery = $addressDelivery;

        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): self
    {
        $this->recipientName = $recipientName;

        return $this;
    }

    public function getRecipientPhone(): ?string
    {
        return $this->recipientPhone;
    }

    public function setRecipientPhone(string $recipientPhone): self
    {
        $this->recipientPhone = $recipientPhone;

        return $this;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): self
    {
        $this->customer = $customer;

        return $this;
    }
}
