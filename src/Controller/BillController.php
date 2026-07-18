<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\BrandLogo;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
final class BillController extends AbstractController
{
    #[Route('/editor/order/{id}/bill', name: 'app_bill')]
    public function index(int $id, OrderRepository $orderRepository, BrandLogo $brandLogo): Response
    {
        $order = $orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException("Commande introuvable pour l'id $id");
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->setIsRemoteEnabled(false);
        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('bill/index.html.twig', [
            'order' => $order,
            'logo_base64' => $brandLogo->getBase64(),
            'logo_mime' => $brandLogo->getMime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture_maison_umu_'.$order->getId().'.pdf"',
            ]
        );
    }
}
