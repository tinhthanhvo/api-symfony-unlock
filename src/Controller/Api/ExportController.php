<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require ROLE_USER for all the actions of this controller
 * @IsGranted("ROLE_USER")
 */
class ExportController extends BaseController
{
    /**
     * @Rest\Get("/users/orders/{id}/export")
     * @param int $id
     * @return Response
     */
    public function exportCustomerInvoice(int $id): Response
    {
        try {
            $order = $this->purchaseOrderRepository->findOneBy([
                'id' => $id,
                'customer' => $this->userLoginInfo->getId(),
                'status' => 4,
                'deleteAt' => null
            ]);
            if ($order) {
                $pdfPathFile = $this->exportData->exportCustomerInvoiceToPdf($order);

                return $this->handleView($this->view(
                    ['success' => $pdfPathFile],
                    Response::HTTP_OK
                ));
            }

            return $this->handleView($this->view(
                ['error' => 'No order was found with this id.'],
                Response::HTTP_NOT_FOUND
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }
}
