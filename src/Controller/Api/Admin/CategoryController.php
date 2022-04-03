<?php

namespace App\Controller\Api\Admin;

use App\Repository\CategoryRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;

class CategoryController extends AbstractFOSRestController
{
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }


}
