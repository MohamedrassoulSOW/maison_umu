<?php

namespace App\Service;

use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoicePdf
{
    public function __construct(
        private Environment $twig,
        private BrandLogo $brandLogo,
    ) {
    }

    public function render(Order $order): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $html = $this->twig->render('bill/index.html.twig', [
            'order' => $order,
            'logo_base64' => $this->brandLogo->getBase64(),
            'logo_mime' => $this->brandLogo->getMime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output() ?: '';
    }

    public function filename(Order $order): string
    {
        $paid = $order->isPaymentCompleted() ? 'payee' : 'non_payee';

        return sprintf('facture_maison_umu_%05d_%s.pdf', (int) $order->getId(), $paid);
    }
}
