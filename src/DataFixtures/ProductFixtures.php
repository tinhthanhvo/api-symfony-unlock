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
        $product = new Product();
        $product->setName('Product name');
        $product->setDescription('Product description');
        $product->setPrice('500000');
        $product->setCreateAt(new \DateTime("now"));

        $category = new Category();
        $category->setName('Category name');
        $category->setCreateAt(new \DateTime("now"));
        $product->setCategory($category);

        $color = new Color();
        $color->setName('Color name');
        $color->setCreateAt(new \DateTime("now"));
        $product->setColor($color);

        $item1 = new ProductItem();
        $item1->setAmount(10);
        $item1->setCreateAt(new \DateTime("now"));

        $size1 = new Size();
        $size1->setValue('35');
        $size1->setCreateAt(new \DateTime("now"));
        $item1->setSize($size1);
        $product->addItem($item1);

        $item2 = new ProductItem();
        $item2->setAmount(10);
        $item2->setCreateAt(new \DateTime("now"));

        $size2 = new Size();
        $size2->setValue('35');
        $size2->setCreateAt(new \DateTime("now"));
        $item2->setSize($size2);
        $product->addItem($item2);

        $gallery = new Gallery();
        $gallery->setPath('cover.jpg');
        $gallery->setCreateAt(new \DateTime("now"));
        $product->addGallery($gallery);

        $manager->persist($product);
        $manager->flush();
    }
}
