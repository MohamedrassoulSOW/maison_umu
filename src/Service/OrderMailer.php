<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
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
        private LoggerInterface $logger,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $subject = $order->isPaymentCompleted()
            ? 'Commande validée — Maison UMU'
            : 'Confirmation de votre commande — Maison UMU';

        $this->send(
            $order,
            $subject,
            'mail/order_confirme.html.twig',
            'order_confirmation'
        );
    }

    public function sendOrderDelivered(Order $order): void
    {
        $this->send(
            $order,
            'Votre commande a été livrée — Maison UMU',
            'mail/order_delivered.html.twig',
            'order_delivered'
        );
    }

    public function sendPaymentConfirmed(Order $order): void
    {
        $this->send(
            $order,
            'Paiement confirmé — Maison UMU',
            'mail/order_payment.html.twig',
            'payment_confirmed'
        );
    }

    private function send(Order $order, string $subject, string $template, string $type): void
    {
        if (!$order->getEmail()) {
            $this->logger->warning('Order mail skipped: missing customer email', [
                'orderId' => $order->getId(),
                'type' => $type,
            ]);

            return;
        }

        $html = $this->twig->render($template, ['order' => $order]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->replyTo($this->fromEmail)
            ->to($order->getEmail())
            ->subject($subject)
            ->html($html)
            ->text(sprintf(
                "Maison UMU\n\nBonjour %s %s,\n\n%s\nCommande n° %s\nTotal : %s CFA\n",
                $order->getFirstName() ?? '',
                $order->getLastName() ?? '',
                $subject,
                $order->getId(),
                $order->getTotalPrice()
            ));

        $logoPath = $this->brandLogo->getPath();
        if ($logoPath) {
            $email->embedFromPath($logoPath, BrandLogo::CID);
        }

        try {
            $this->mailer->send($email);
            $this->logger->info('Order mail sent', [
                'orderId' => $order->getId(),
                'to' => $order->getEmail(),
                'type' => $type,
                'from' => $this->fromEmail,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Order mail failed: '.$e->getMessage(), [
                'orderId' => $order->getId(),
                'to' => $order->getEmail(),
                'type' => $type,
                'from' => $this->fromEmail,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
