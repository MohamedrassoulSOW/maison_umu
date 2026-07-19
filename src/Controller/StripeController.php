<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderPaymentCompleter;
use App\Service\OrderStock;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class StripeController extends AbstractController
{
    public function __construct(
        private OrderPaymentCompleter $paymentCompleter,
        private OrderStock $orderStock,
        private string $stripeSecretKey,
        private string $stripeWebhookSecret,
        private LoggerInterface $logger,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(
        Request $request,
        SessionInterface $session,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $checkoutSessionId = (string) $request->query->get('session_id', '');
        $order = null;
        $trackingUrl = null;

        if ($checkoutSessionId !== '') {
            try {
                $checkoutSession = StripeSession::retrieve([
                    'id' => $checkoutSessionId,
                    'expand' => ['payment_intent'],
                ]);

                if (($checkoutSession->payment_status ?? null) === 'paid') {
                    $orderId = $checkoutSession->metadata->orderId ?? null;
                    $paymentIntent = $checkoutSession->payment_intent ?? null;
                    if (!$orderId && is_object($paymentIntent)) {
                        $orderId = $paymentIntent->metadata->orderId ?? null;
                    }

                    $paidAmount = null;
                    if (is_object($paymentIntent) && isset($paymentIntent->amount_received)) {
                        $paidAmount = (int) $paymentIntent->amount_received;
                    } elseif (is_object($paymentIntent) && isset($paymentIntent->amount)) {
                        $paidAmount = (int) $paymentIntent->amount;
                    } elseif (isset($checkoutSession->amount_total)) {
                        $paidAmount = (int) $checkoutSession->amount_total;
                    }

                    if ($orderId && $paidAmount !== null) {
                        $this->paymentCompleter->completeStripePayment((int) $orderId, $paidAmount);
                    }

                    $order = $orderId ? $orderRepository->find($orderId) : null;
                    if ($order) {
                        $hadToken = (bool) $order->getTrackingToken();
                        $token = $order->ensureTrackingToken();
                        if (!$hadToken) {
                            $entityManager->flush();
                        }
                        $trackingUrl = $this->generateUrl('app_order_track', ['token' => $token]);
                    }

                    $session->set('cart', []);
                    $session->remove('pending_stripe_order_id');
                }
            } catch (\Throwable $e) {
                $this->logger->error('Stripe success verification failed: '.$e->getMessage());
            }
        }

        return $this->render('stripe/success.html.twig', [
            'order' => $order,
            'trackingUrl' => $trackingUrl,
        ]);
    }

    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(
        SessionInterface $session,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $orderId = $session->get('pending_stripe_order_id');
        if ($orderId) {
            $order = $orderRepository->find($orderId);
            // Annulation Checkout : commande Stripe non payée → supprimer (stock jamais réservé)
            if ($order
                && $order->getPaymentMethod() === 'stripe'
                && !$order->isPaymentCompleted()
                && !$this->orderStock->wasReserved($order)
            ) {
                $entityManager->remove($order);
                $entityManager->flush();
            }
            $session->remove('pending_stripe_order_id');
        }

        return $this->render('stripe/cancel.html.twig');
    }

    #[Route('/stripe/notify', name: 'app_stripe_notify', methods: ['POST'])]
    public function stripeNotify(
        Request $request,
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->stripeWebhookSecret
            );
        } catch (\UnexpectedValueException) {
            return new Response('payload invalide', 400);
        } catch (\Stripe\Exception\SignatureVerificationException) {
            return new Response('Signature invalide', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $orderId = $paymentIntent->metadata->orderId ?? null;
            if ($orderId) {
                $this->paymentCompleter->completeStripePayment(
                    (int) $orderId,
                    (int) $paymentIntent->amount
                );
            }
        }

        return new Response('event reçu', 200);
    }
}
