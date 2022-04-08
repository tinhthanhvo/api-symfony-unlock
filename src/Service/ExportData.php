<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ExportData extends AbstractController
{
    public function generate_pdf(): void
    {

        $options = new Options();
        $options->set('defaultFont', 'Roboto');

        $dompdf = new Dompdf($options);

        $data = [
            'headline' => 'my headline'
        ];
        $html = $this->renderView('api/pdf/user_order.html.twig', [
            'headline' => "Test pdf generator"
        ]);


        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("testpdf.pdf", [
            "Attachment" => true
        ]);
    }
}
