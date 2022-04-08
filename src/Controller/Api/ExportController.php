<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Entity\Order;
use App\Form\OrderExportType;
use App\Repository\PurchaseOrderRepository;
use App\Service\ExportData;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require ROLE_USER for all the actions of this controller
 * @IsGranted("ROLE_USER")
 */
class ExportController extends BaseController
{
    /**
     * @Rest\Get("/users/orders/export")
     * @return Response
     */
    public function exportPdfForUser(): Response
    {
        try {
            $order = new Order();

            $form = $this->createForm(OrderExportType::class, $order);
            $form->submit(json_decode($request->getContent(), true));
            if ($form->isSubmitted() && $form->isValid()) {
                $cartItem = $this->cartRepository->findOneBy([
                    'productItem' => $payload['productItem'],
                    'user' => $this->userLoginInfo->getId()
                ]);

                $this->exportPdf->generate_pdf();
            }

            $errorsMessage = $this->getFormErrorMessage($form);

            return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }
}
