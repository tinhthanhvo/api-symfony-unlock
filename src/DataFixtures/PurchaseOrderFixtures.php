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
        $user->setCreateAt();

        $category = new Category();
        $category->setName('Category name');
        $category->setCreateAt();

        $color = new Color();
        $color->setName('Color name');
        $color->setCreateAt();

        $size = new Size();
        $size->setValue('35');
        $size->setCreateAt();

        $firstProduct = new Product();
        $firstProduct->setCreateAt();
        $firstProduct->setCategory($category);
        $firstProduct->setColor($color);
        $firstProduct->setName('Product name 1');
        $firstProduct->setDescription('Product description 1');
        $firstProduct->setPrice(30);

        $firstProductGallery = new Gallery();
        $firstProductGallery->setProduct($firstProduct);
        $firstProductGallery->setPath('first-cover.jpg');
        $firstProductGallery->setCreateAt();

        $firstProductItem = new ProductItem();
        $firstProductItem->setCreateAt();
        $firstProductItem->setProduct($firstProduct);
        $firstProductItem->setSize($size);
        $firstProductItem->setAmount(10);

        $firstCartItem = new Cart();
        $firstCartItem->setCreateAt();
        $firstCartItem->setProductItem($firstProductItem);
        $firstCartItem->setUser($user);
        $firstCartItem->setAmount(1);
        $firstCartItem->setPrice($firstProduct->getPrice());

        $firstOrderDetail = new OrderDetail();
        $firstOrderDetail->setCreateAt();
        $firstOrderDetail->setProductItem($firstProductItem);
        $firstOrderDetail->setAmount($firstCartItem->getAmount());
        $firstOrderDetail->setPrice($firstCartItem->getAmount()*$firstProductItem->getProduct()->getPrice());

        // Second product
        $secondProduct = new Product();
        $secondProduct->setCreateAt();
        $secondProduct->setCategory($category);
        $secondProduct->setColor($color);
        $secondProduct->setName('Product name 2');
        $secondProduct->setDescription('Product description 2');
        $secondProduct->setPrice(50);

        $secondProductGallery = new Gallery();
        $secondProductGallery->setCreateAt();
        $secondProductGallery->setProduct($secondProduct);
        $secondProductGallery->setPath('second-cover.jpg');

        $secondProductItem = new ProductItem();
        $secondProductItem->setCreateAt();
        $secondProductItem->setProduct($secondProduct);
        $secondProductItem->setSize($size);
        $secondProductItem->setAmount(5);

        $secondCartItem = new Cart();
        $secondCartItem->setProductItem($secondProductItem);
        $secondCartItem->setUser($user);
        $secondCartItem->setAmount(1);
        $secondCartItem->setPrice($secondProduct->getPrice());
        $secondCartItem->setCreateAt();

        $secondOrderDetail = new OrderDetail();
        $secondOrderDetail->setCreateAt();
        $secondOrderDetail->setProductItem($secondProductItem);
        $secondOrderDetail->setAmount($secondCartItem->getAmount());
        $secondOrderDetail->setPrice($secondCartItem->getAmount()*$secondProductItem->getProduct()->getPrice());


        $purchaseOrder = new PurchaseOrder($user);
        $purchaseOrder->setCreateAt();
        $purchaseOrder->setRecipientName('Recipient Name');
        $purchaseOrder->setRecipientEmail('Recipient Email');
        $purchaseOrder->setRecipientPhone('0123456789');
        $purchaseOrder->setAddressDelivery('Cai Khe, Ninh Kieu');
        $purchaseOrder->setStatus('1');
        $purchaseOrder->addOrderItem($firstOrderDetail);
        $purchaseOrder->addOrderItem($secondOrderDetail);
        $purchaseOrder->setAmount(2);
        $purchaseOrder->setTotalPrice(80);

        $manager->persist($purchaseOrder);
        $manager->flush();
    }
}