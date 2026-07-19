<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderMailer;
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
        private OrderMailer $orderMailer,
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

                    $order = $orderId ? $orderRepository->find($orderId) : null;
                    if ($order && !$order->isPaymentCompleted()) {
                        $order->setIsPaymentCompleted(true);
                        $order->setPaymentMethod('stripe');
                        $order->setPayOnDelivery(false);
                        $entityManager->flush();

                        try {
                            $this->orderMailer->sendPaymentConfirmed($order);
                            $this->orderMailer->sendOrderConfirmation($order);
                        } catch (\Throwable) {
                            // Payment saved even if mail fails
                        }
                    }

                    $session->set('cart', []);
                    $session->remove('pending_stripe_order_id');
                }
            } catch (\Throwable $e) {
                $this->logger->error('Stripe success verification failed: '.$e->getMessage());
            }
        }

        return $this->render('stripe/success.html.twig');
    }

    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig');
    }

    #[Route('/stripe/notify', name: 'app_stripe_notify', methods: ['POST'])]
    public function stripeNotify(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
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
            $order = $orderId ? $orderRepository->find($orderId) : null;

            if ($order && !$order->isPaymentCompleted()) {
                $cartPrice = (int) round((float) $order->getTotalPrice());
                $stripeTotalAmount = (int) $paymentIntent->amount;

                if ($cartPrice === $stripeTotalAmount) {
                    $order->setIsPaymentCompleted(true);
                    $order->setPaymentMethod('stripe');
                    $order->setPayOnDelivery(false);
                    $entityManager->flush();

                    try {
                        $this->orderMailer->sendPaymentConfirmed($order);
                        $this->orderMailer->sendOrderConfirmation($order);
                    } catch (\Throwable) {
                        // Payment saved even if mail fails
                    }
                } else {
                    $this->logger->warning('Stripe amount mismatch', [
                        'orderId' => $order->getId(),
                        'orderTotal' => $cartPrice,
                        'stripeAmount' => $stripeTotalAmount,
                    ]);
                }
            }
        }

        return new Response('event reçu', 200);
    }
}
