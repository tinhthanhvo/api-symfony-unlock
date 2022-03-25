<?php

namespace App\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/products")
     */
    public function getProducts(): Response
    {
        $products = [
            'name' => 'Product name',
            'description' => 'Product description'
        ];

        return $this->handleView($this->view($products));
    }
}
