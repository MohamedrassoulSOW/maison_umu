<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripePayment
{
    private ?string $redirectUrl = null;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $stripeSecretKey,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
        Stripe::setApiVersion('2023-10-16');
    }

    public function startPayment(array $cart, float|int $shippingConst, int $orderId): void
    {
        $lineItems = [];

        foreach ($cart['cart'] as $item) {
            if (!$item['product']) {
                continue;
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'xof',
                    'product_data' => [
                        'name' => $item['product']->getName(),
                    ],
                    // XOF is a zero-decimal currency
                    'unit_amount' => (int) round((float) $item['product']->getPrice()),
                ],
                'quantity' => (int) $item['quantity'],
            ];
        }

        if ($shippingConst > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'xof',
                    'product_data' => [
                        'name' => 'Frais de livraison',
                    ],
                    'unit_amount' => (int) round((float) $shippingConst),
                ],
                'quantity' => 1,
            ];
        }

        $successUrl = $this->urlGenerator->generate(
            'app_stripe_success',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ).'?session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = $this->urlGenerator->generate(
            'app_stripe_cancel',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['SN', 'MA', 'FR'],
            ],
            'metadata' => [
                'orderId' => (string) $orderId,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'orderId' => (string) $orderId,
                ],
            ],
        ]);

        $this->redirectUrl = $session->url;
    }

    public function getStripeRedirectUrl(): string
    {
        return (string) $this->redirectUrl;
    }
}
