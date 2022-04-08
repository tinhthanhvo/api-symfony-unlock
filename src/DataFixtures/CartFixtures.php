<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\Size;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CartFixtures extends Fixture
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
        $firstProduct->setCategory($category);
        $firstProduct->setColor($color);
        $firstProduct->setName('Product name 1');
        $firstProduct->setDescription('Product description 1');
        $firstProduct->setPrice(300000);
        $firstProduct->setCreateAt();

        $firstProductGallery = new Gallery();
        $firstProductGallery->setProduct($firstProduct);
        $firstProductGallery->setPath('first-cover.jpg');
        $firstProductGallery->setCreateAt();

        $firstProductItem = new ProductItem();
        $firstProductItem->setProduct($firstProduct);
        $firstProductItem->setSize($size);
        $firstProductItem->setAmount(10);
        $firstProductItem->setCreateAt();

        $firstCartItem = new Cart();
        $firstCartItem->setProductItem($firstProductItem);
        $firstCartItem->setUser($user);
        $firstCartItem->setAmount(1);
        $firstCartItem->setPrice($firstProduct->getPrice());
        $firstCartItem->setCreateAt();

        $manager->persist($firstCartItem);

        // Second product
        $secondProduct = new Product();
        $secondProduct->setCategory($category);
        $secondProduct->setColor($color);
        $secondProduct->setName('Product name 2');
        $secondProduct->setDescription('Product description 2');
        $secondProduct->setPrice(500000);
        $secondProduct->setCreateAt();

        $secondProductGallery = new Gallery();
        $secondProductGallery->setProduct($secondProduct);
        $secondProductGallery->setPath('second-cover.jpg');
        $secondProductGallery->setCreateAt();

        $secondProductItem = new ProductItem();
        $secondProductItem->setProduct($secondProduct);
        $secondProductItem->setSize($size);
        $secondProductItem->setAmount(5);
        $secondProductItem->setCreateAt();

        $secondCartItem = new Cart();
        $secondCartItem->setProductItem($secondProductItem);
        $secondCartItem->setUser($user);
        $secondCartItem->setAmount(1);
        $secondCartItem->setPrice($secondProduct->getPrice());
        $secondCartItem->setCreateAt();

        $manager->persist($secondCartItem);
        $manager->flush();
    }
}
