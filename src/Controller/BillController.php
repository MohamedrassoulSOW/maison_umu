<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\InvoicePdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
final class BillController extends AbstractController
{
    #[Route('/editor/order/{id}/bill', name: 'app_bill')]
    public function index(int $id, OrderRepository $orderRepository, InvoicePdf $invoicePdf): Response
    {
        $order = $orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException("Commande introuvable pour l'id $id");
        }

        $pdf = $invoicePdf->render($order);

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$invoicePdf->filename($order).'"',
            ]
        );
    }
}
