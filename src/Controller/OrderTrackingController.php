<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderTrackingController extends AbstractController
{
    #[Route('/suivi/{token}', name: 'app_order_track', methods: ['GET', 'POST'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function track(
        string $token,
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        OrderMailer $orderMailer,
    ): Response {
        $order = $orderRepository->findOneBy(['trackingToken' => $token]);
        if (!$order) {
            throw $this->createNotFoundException('Lien de suivi invalide ou expiré.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('order_survey'.$token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            if (!$order->canSubmitSatisfaction()) {
                $this->addFlash('danger', 'L’avis ne peut plus être envoyé pour cette commande.');

                return $this->redirectToRoute('app_order_track', ['token' => $token]);
            }

            $score = $request->request->getInt('score');
            if ($score < 1 || $score > 5) {
                $this->addFlash('danger', 'Merci de choisir une note entre 1 et 5.');

                return $this->redirectToRoute('app_order_track', ['token' => $token, '_fragment' => 'avis']);
            }

            $comment = trim($request->request->getString('comment'));
            if (mb_strlen($comment) > 2000) {
                $comment = mb_substr($comment, 0, 2000);
            }

            $order->setSatisfactionScore($score);
            $order->setSatisfactionComment($comment !== '' ? $comment : null);
            $order->setSatisfactionSubmittedAt(new \DateTimeImmutable());
            $entityManager->flush();

            try {
                $orderMailer->notifySatisfactionReceived($order);
            } catch (\Throwable) {
                // Feedback saved even if shop notification fails
            }

            $this->addFlash('success', 'Merci pour votre avis !');

            return $this->redirectToRoute('app_order_track', ['token' => $token, '_fragment' => 'avis']);
        }

        return $this->render('order/track.html.twig', [
            'order' => $order,
            'steps' => $order->getTrackingSteps(),
        ]);
    }
}
