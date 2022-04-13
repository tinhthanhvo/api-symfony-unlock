<?php

namespace App\Service;

use App\Entity\PurchaseOrder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ExportData extends AbstractController
{
    /**
     * Create PDF file with twig template
     * @param PurchaseOrder $purchaseOrder
     * @return string
     */
    public function exportCustomerInvoiceToPdf(PurchaseOrder $purchaseOrder): string
    {
        $fileName = 'Invoice_' . date('YmdHis') . '.pdf';

        $options = new Options();
        $options->setIsRemoteEnabled(true);
        $options->setIsHtml5ParserEnabled(true);
        $dompdf = new Dompdf($options);

        $html = $this->renderView('pdf/user_order.html.twig', [
            'invoice' => $purchaseOrder,
            'exportDate' => new \DateTime("now")
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents($fileName, $output);

        return 'http://127.0.0.1:8080/' . $fileName;
    }
}
