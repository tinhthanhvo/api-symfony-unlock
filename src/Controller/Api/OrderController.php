<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/api/order', name: 'app_api_order')]
    public function index(): Response
    {
        return $this->render('api/order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }
}
