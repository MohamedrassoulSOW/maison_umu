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
        private MobileMoneyConfig $mobileMoney,
        private InvoicePdf $invoicePdf,
        private string $fromEmail,
        private string $fromName,
    ) {
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $subject = match (true) {
            $order->getPaymentMethod() === 'cod' => 'Commande validée — facture à régler à la livraison — Maison UMU',
            $order->isPaymentCompleted() => 'Commande validée — Maison UMU',
            default => 'Confirmation de votre commande — Maison UMU',
        };

        // COD : facture non payée en pièce jointe
        $attachInvoice = $order->getPaymentMethod() === 'cod';

        $this->send(
            $order,
            $subject,
            'mail/order_confirme.html.twig',
            'order_confirmation',
            $attachInvoice
        );
    }

    public function sendOrderDelivered(Order $order): void
    {
        $this->send(
            $order,
            'Votre commande a été livrée — Maison UMU',
            'mail/order_delivered.html.twig',
            'order_delivered',
            false
        );
    }

    /** E-mail après paiement reçu (Wave / OM / Stripe) + facture payée en PJ. */
    public function sendPaymentConfirmed(Order $order): void
    {
        $this->send(
            $order,
            'Paiement confirmé — facture en pièce jointe — Maison UMU',
            'mail/order_payment.html.twig',
            'payment_confirmed',
            true
        );
    }

    private function send(
        Order $order,
        string $subject,
        string $template,
        string $type,
        bool $attachInvoice = false,
    ): void {
        if (!$order->getEmail()) {
            $this->logger->warning('Order mail skipped: missing customer email', [
                'orderId' => $order->getId(),
                'type' => $type,
            ]);

            return;
        }

        $html = $this->twig->render($template, [
            'order' => $order,
            'mobileMoneyPhone' => $this->mobileMoney->getDisplayPhone(),
        ]);

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

        if ($attachInvoice) {
            try {
                $pdf = $this->invoicePdf->render($order);
                if ($pdf !== '') {
                    $email->attach($pdf, $this->invoicePdf->filename($order), 'application/pdf');
                }
            } catch (\Throwable $e) {
                $this->logger->error('Invoice PDF attach failed: '.$e->getMessage(), [
                    'orderId' => $order->getId(),
                    'type' => $type,
                    'exception' => $e,
                ]);
            }
        }

        try {
            $this->mailer->send($email);
            $this->logger->info('Order mail sent', [
                'orderId' => $order->getId(),
                'to' => $order->getEmail(),
                'type' => $type,
                'from' => $this->fromEmail,
                'invoiceAttached' => $attachInvoice,
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
