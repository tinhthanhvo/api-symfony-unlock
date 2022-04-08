<?php

namespace App\Controller\Api\Admin;

use App\Controller\BaseController;
use App\Form\OrderExportType;
use App\Form\ProductExportType;
use App\Service\ExportData;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Require ROLE_ADMIN for all the actions of this controller
 * @IsGranted("ROLE_ADMIN")
 */
class ReportController extends BaseController
{
    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Rest\Post("/orders/export-csv")
     * @param Request $request
     * @return Response
     */
    public function exportCsvPurchaseOrderInfo(Request $request): Response
    {
        try {
            $form = $this->createForm(OrderExportType::class, []);
            $payload = json_decode($request->getContent(), true);

            $form->submit($payload);
            if ($form->isSubmitted() && $form->isValid()) {
                $application = new Application($this->kernel);
                $application->setAutoExit(false);

                $input = new ArrayInput([
                    'command' => 'report:purchase-order',
                    '--name' => (!empty($payload['fileName'])) ? $payload['fileName'] : 'Report_Order_' . date('YmdHis'),
                    'status' => (!empty($payload['status'])) ? $payload['status'] : 0,
                    'fromDate' => (!empty($payload['fromDate'])) ? $payload['fromDate'] : 0,
                    'toDate' => (!empty($payload['toDate'])) ? $payload['toDate'] : 0
                ]);

                $output = new BufferedOutput();
                $application->run($input, $output);

                return $this->handleView($this->view(['success' => $output->fetch()], Response::HTTP_OK));
            }

            return $this->handleView($this->view(
                ['error' => $this->getFormErrorMessage($form)],
                Response::HTTP_BAD_REQUEST
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @Rest\Post("/products/export-csv")
     * @param Request $request
     * @return Response
     */
    public function exportCsvProductInfo(Request $request): Response
    {
        try {
            $form = $this->createForm(ProductExportType::class, []);
            $payload = json_decode($request->getContent(), true);

            $form->submit($payload);
            if ($form->isSubmitted() && $form->isValid()) {
                $application = new Application($this->kernel);
                $application->setAutoExit(false);

                $input = new ArrayInput([
                    'command' => 'report:product',
                    '--name' => (!empty($payload['fileName'])) ? $payload['fileName'] : 'Report_Product_' . date('YmdHis'),
                    'product_id' => (!empty($payload['productId'])) ? $payload['productId'] : 0,
                    'fromDate' => (!empty($payload['fromDate'])) ? $payload['fromDate'] : 0,
                    'toDate' => (!empty($payload['toDate'])) ? $payload['toDate'] : 0
                ]);

                $output = new BufferedOutput();
                $application->run($input, $output);

                return $this->handleView($this->view(['success' => $output->fetch()], Response::HTTP_OK));
            }

            return $this->handleView($this->view(
                ['error' => $this->getFormErrorMessage($form)],
                Response::HTTP_BAD_REQUEST
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
