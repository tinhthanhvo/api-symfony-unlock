<?php

namespace App\Service;

use App\Entity\PurchaseOrder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ExportData extends AbstractController
{
    /**
     * Create PDF file with twig template
     * @param PurchaseOrder $purchaseOrder
     * @return string
     */
    public function exportCustomerInvoiceToPdf(PurchaseOrder $purchaseOrder): string
    {
        // dd($purchaseOrder);

        $options = new Options();
        $options->set('defaultFont', 'Roboto');
        $dompdf = new Dompdf($options);

        // $data = [
        //     'headline' => 'my headline'
        // ];
        // $html = $this->renderView('pdf/user_order.html.twig', [
        //     'headline' => "Test pdf generator"
        // ]);

        $html = $this->renderView('pdf/user_order.html.twig', [
            'invoice' => $purchaseOrder,
            'exportDate' => new \DateTime("now")
        ]);


        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("testpdf.pdf", [
            "Attachment" => true
        ]);

        return "";
    }
}
