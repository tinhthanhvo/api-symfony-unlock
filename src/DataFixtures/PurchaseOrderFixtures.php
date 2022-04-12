<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Gallery;
use App\Entity\OrderDetail;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\PurchaseOrder;
use App\Entity\Size;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PurchaseOrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@gmail.com');
        $user->setPassword('23abncH');
        $user->setFullName('User full name');
        $user->setPhoneNumber('0908855655');
        $user->setRoles(['ROLE_USER']);

        $category = new Category();
        $category->setName('Category name');

        $color = new Color();
        $color->setName('Color name');

        $size = new Size();
        $size->setValue('35');

        $firstProduct = new Product();
        $firstProduct->setCategory($category);
        $firstProduct->setColor($color);
        $firstProduct->setName('Product name 1');
        $firstProduct->setDescription('Product description 1');
        $firstProduct->setPrice(30);

        $firstProductGallery = new Gallery();
        $firstProductGallery->setProduct($firstProduct);
        $firstProductGallery->setPath('first-cover.jpg');

        $firstProductItem = new ProductItem();
        $firstProductItem->setProduct($firstProduct);
        $firstProductItem->setSize($size);
        $firstProductItem->setAmount(10);

        $firstCartItem = new Cart();
        $firstCartItem->setProductItem($firstProductItem);
        $firstCartItem->setUser($user);
        $firstCartItem->setAmount(1);
        $firstCartItem->setPrice($firstProduct->getPrice());

        $firstOrderDetail = new OrderDetail();
        $firstOrderDetail->setProductItem($firstProductItem);
        $firstOrderDetail->setAmount($firstCartItem->getAmount());
        $firstOrderDetail->setPrice($firstCartItem->getAmount() * $firstProductItem->getProduct()->getPrice());

        // Second product
        $secondProduct = new Product();
        $secondProduct->setCategory($category);
        $secondProduct->setColor($color);
        $secondProduct->setName('Product name 2');
        $secondProduct->setDescription('Product description 2');
        $secondProduct->setPrice(50);

        $secondProductGallery = new Gallery();
        $secondProductGallery->setProduct($secondProduct);
        $secondProductGallery->setPath('second-cover.jpg');

        $secondProductItem = new ProductItem();
        $secondProductItem->setProduct($secondProduct);
        $secondProductItem->setSize($size);
        $secondProductItem->setAmount(5);

        $secondCartItem = new Cart();
        $secondCartItem->setProductItem($secondProductItem);
        $secondCartItem->setUser($user);
        $secondCartItem->setAmount(1);
        $secondCartItem->setPrice($secondProduct->getPrice());

        $secondOrderDetail = new OrderDetail();
        $secondOrderDetail->setProductItem($secondProductItem);
        $secondOrderDetail->setAmount($secondCartItem->getAmount());
        $secondOrderDetail->setPrice($secondCartItem->getAmount() * $secondProductItem->getProduct()->getPrice());

        $purchaseOrder = new PurchaseOrder($user, 0);
        $purchaseOrder->setRecipientName('Recipient Name');
        $purchaseOrder->setRecipientEmail('Recipient Email');
        $purchaseOrder->setRecipientPhone('0123456789');
        $purchaseOrder->setAddressDelivery('Cai Khe, Ninh Kieu');
        $purchaseOrder->setStatus('1');
        $purchaseOrder->addOrderItem($firstOrderDetail);
        $purchaseOrder->addOrderItem($secondOrderDetail);
        $purchaseOrder->setAmount(2);
        $purchaseOrder->setTotalPrice(80);

        // Other Purchase order with third product
        $thirdProduct = new Product();
        $thirdProduct->setCategory($category);
        $thirdProduct->setColor($color);
        $thirdProduct->setName('Product name 3');
        $thirdProduct->setDescription('Product description 3');
        $thirdProduct->setPrice(420000);

        $thirdProductGallery = new Gallery();
        $thirdProductGallery->setProduct($thirdProduct);
        $thirdProductGallery->setPath('third-cover.jpg');

        $thirdProductItem = new ProductItem();
        $thirdProductItem->setProduct($thirdProduct);
        $thirdProductItem->setSize($size);
        $thirdProductItem->setAmount(2);

        $thirdOrderDetail = new OrderDetail();
        $thirdOrderDetail->setProductItem($thirdProductItem);
        $thirdOrderDetail->setAmount(1);
        $thirdOrderDetail->setPrice(420000);

        $secondPurchaseOrder = new PurchaseOrder($user, 0);
        $secondPurchaseOrder->setRecipientName('Recipient Name');
        $secondPurchaseOrder->setRecipientEmail('Recipient Email');
        $secondPurchaseOrder->setRecipientPhone('0908633533');
        $secondPurchaseOrder->setAddressDelivery('Tan An, Ninh Kieu');
        $secondPurchaseOrder->setStatus('4');
        $secondPurchaseOrder->addOrderItem($thirdOrderDetail);
        $secondPurchaseOrder->setAmount(1);
        $secondPurchaseOrder->setTotalPrice(420000);

        $manager->persist($purchaseOrder);
        $manager->persist($secondPurchaseOrder);

        $manager->flush();
    }
}
