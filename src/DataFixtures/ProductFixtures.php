<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Product;
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

        $manager->persist($product);
        $manager->flush();
    }
}
