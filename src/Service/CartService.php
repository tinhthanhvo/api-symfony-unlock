<?php

namespace App\Service;

use App\Controller\BaseController;
use App\Entity\Cart;
use App\Entity\User;
use App\Form\CartItemType;
use App\Repository\CartRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CartService extends AbstractController
{
    /**
     * @var CartRepository
     */
    private $cartRepository;
    /**
     * @var User|null
     */
    private $userLoginInfo;

    public function __construct(
        CartRepository $cartRepository,
        GetUserInfo $userLogin
    ) {
        $this->cartRepository = $cartRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
    }

    /**
     * @param array $payload
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addCart(array $payload): bool
    {
        $cartItem = $this->cartRepository->findOneBy([
            'productItem' => $payload['productItem'],
            'user' => $this->userLoginInfo->getId()
        ]);

        if (!$cartItem) {
            $cartItem = new Cart();
            $cartItem->setUser($this->userLoginInfo);
        } else {
            $amount = $cartItem->getAmount() + $payload['amount'];
            if ($amount > $cartItem->getProductItem()->getAmount()) {
                return false;
            }
            $payload['amount'] = $amount;
        }

        $form = $this->createForm(CartItemType::class, $cartItem);
        $form->submit($payload);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->cartRepository->add($cartItem);

            return true;
        }

        return false;
    }
}
