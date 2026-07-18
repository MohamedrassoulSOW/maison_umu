<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private BrandLogo $brandLogo,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $this->send(
            $order,
            'Confirmation de votre commande — Maison UMU',
            'mail/order_confirme.html.twig'
        );
    }

    public function sendOrderDelivered(Order $order): void
    {
        $this->send(
            $order,
            'Votre commande a été livrée — Maison UMU',
            'mail/order_delivered.html.twig'
        );
    }

    public function sendPaymentConfirmed(Order $order): void
    {
        $this->send(
            $order,
            'Paiement confirmé — Maison UMU',
            'mail/order_payment.html.twig'
        );
    }

    private function send(Order $order, string $subject, string $template): void
    {
        if (!$order->getEmail()) {
            return;
        }

        $html = $this->twig->render($template, ['order' => $order]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($order->getEmail())
            ->subject($subject)
            ->html($html);

        $logoPath = $this->brandLogo->getPath();
        if ($logoPath) {
            $email->embedFromPath($logoPath, BrandLogo::CID);
        }

        $this->mailer->send($email);
    }
}
