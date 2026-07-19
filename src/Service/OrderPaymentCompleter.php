<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Marque une commande Stripe comme payée une seule fois :
 * vérif montant, réserve le stock, e-mail facture.
 */
class OrderPaymentCompleter
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderStock $orderStock,
        private readonly OrderMailer $orderMailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return bool true si le paiement vient d’être enregistré
     */
    public function completeStripePayment(int $orderId, int $paidAmountCents): bool
    {
        $this->em->beginTransaction();
        try {
            /** @var Order|null $order */
            $order = $this->em->createQueryBuilder()
                ->select('o')
                ->from(Order::class, 'o')
                ->where('o.id = :id')
                ->setParameter('id', $orderId)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$order) {
                $this->em->rollback();

                return false;
            }

            if ($order->isPaymentCompleted()) {
                $this->em->commit();

                return false;
            }

            $expected = (int) round((float) $order->getTotalPrice());
            if ($expected !== $paidAmountCents) {
                $this->logger->warning('Stripe amount mismatch', [
                    'orderId' => $order->getId(),
                    'orderTotal' => $expected,
                    'stripeAmount' => $paidAmountCents,
                ]);
                $this->em->rollback();

                return false;
            }

            $order->setIsPaymentCompleted(true);
            $order->setPaymentMethod('stripe');
            $order->setPayOnDelivery(false);
            $order->ensureTrackingToken();
            $this->em->flush();

            $this->orderStock->reserve($order);
            $this->em->commit();
        } catch (\Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            $this->logger->error('Stripe payment complete failed: '.$e->getMessage(), [
                'orderId' => $orderId,
                'exception' => $e,
            ]);

            return false;
        }

        $order = $this->em->find(Order::class, $orderId);
        if (!$order) {
            return false;
        }

        try {
            $this->orderMailer->sendPaymentConfirmed($order);
        } catch (\Throwable) {
            // Payment saved even if mail fails
        }

        return true;
    }
}
