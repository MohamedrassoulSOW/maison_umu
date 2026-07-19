<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
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

        $this->send(
            $order,
            $subject,
            'mail/order_confirme.html.twig',
            'order_confirmation',
            $order->getPaymentMethod() === 'cod'
        );
    }

    public function sendOrderDelivered(Order $order): void
    {
        $this->send(
            $order,
            'Commande réceptionnée — votre avis nous intéresse — Maison UMU',
            'mail/order_delivered.html.twig',
            'order_delivered',
            false
        );
    }

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

    /** Notifie la boutique qu’un avis client a été déposé. */
    public function notifySatisfactionReceived(Order $order): void
    {
        $score = $order->getSatisfactionScore() ?? 0;
        $comment = $order->getSatisfactionComment() ?: '—';
        $html = sprintf(
            '<p>Nouvel avis satisfaction</p><p>Commande n° %05d — %s %s</p><p>Note : <strong>%d/5</strong></p><p>Commentaire :</p><p>%s</p>',
            (int) $order->getId(),
            htmlspecialchars($order->getFirstName() ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($order->getLastName() ?? '', ENT_QUOTES, 'UTF-8'),
            $score,
            nl2br(htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'))
        );

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($this->fromEmail)
            ->replyTo($order->getEmail() ?: $this->fromEmail)
            ->subject(sprintf('Avis client %d/5 — commande n° %05d', $score, (int) $order->getId()))
            ->html($html);

        $this->mailer->send($email);
    }

    public function trackingUrl(Order $order): string
    {
        $hadToken = $order->getTrackingToken() !== null && $order->getTrackingToken() !== '';
        $token = $order->ensureTrackingToken();
        if (!$hadToken) {
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        }

        return $this->urlGenerator->generate(
            'app_order_track',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
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

        $trackingUrl = $this->trackingUrl($order);
        $paymentLinks = null;
        if ($order->isMobileMoney() && !$order->isPaymentCompleted()) {
            $paymentLinks = $this->mobileMoney->paymentLinksFor(
                $order->getPaymentMethod(),
                (float) $order->getTotalPrice()
            );
        }

        $html = $this->twig->render($template, [
            'order' => $order,
            'mobileMoneyPhone' => $this->mobileMoney->getDisplayPhone(),
            'paymentLinks' => $paymentLinks,
            'trackingUrl' => $trackingUrl,
        ]);

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->replyTo($this->fromEmail)
            ->to($order->getEmail())
            ->subject($subject)
            ->html($html)
            ->text(sprintf(
                "Maison UMU\n\nBonjour %s %s,\n\n%s\nCommande n° %s\nTotal : %s CFA\nSuivi : %s\n",
                $order->getFirstName() ?? '',
                $order->getLastName() ?? '',
                $subject,
                $order->getId(),
                $order->getTotalPrice(),
                $trackingUrl
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
