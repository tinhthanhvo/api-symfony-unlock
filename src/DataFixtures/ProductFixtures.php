<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\Size;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $category = new Category();
        $category->setName('Category name');

        $color = new Color();
        $color->setName('Color name');

        $item = new ProductItem();
        $item->setAmount(10);

        $size = new Size();
        $size->setValue('35');
        $item->setSize($size);

        $gallery = new Gallery();
        $gallery->setPath('cover.jpg');

        $gallery2 = new Gallery();
        $gallery2->setPath('cover.jpg');

        $product1 = new Product();
        $product1->setName('Product name 1');
        $product1->setDescription('Product description 1');
        $product1->setPrice(300000);
        $product1->setCategory($category);
        $product1->setColor($color);
        $product1->addItem($item);
        $product1->addGallery($gallery);
        $manager->persist($product1);

        $product2 = new Product();
        $product2->setName('Product name 2');
        $product2->setDescription('Product description 2');
        $product2->setPrice(500000);
        $product2->setCategory($category);
        $product2->setColor($color);

        $item2 = new ProductItem();
        $item2->setAmount(10);
        $item2->setSize($size);
        $product2->addItem($item2);
        $product2->addGallery($gallery2);
        $manager->persist($product2);

        $manager->flush();
    }
}
