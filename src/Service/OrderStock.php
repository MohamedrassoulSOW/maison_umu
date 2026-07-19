<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

/** Réserve / restaure le stock des lignes d’une commande. */
class OrderStock
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function reserve(Order $order): void
    {
        foreach ($order->getOrderProducts() as $line) {
            $product = $line->getProduct();
            $qty = (int) $line->getQte();
            if (!$product || $qty <= 0) {
                continue;
            }
            $product->setStock(max(0, (int) $product->getStock() - $qty));
        }
        $this->em->flush();
    }

    public function restore(Order $order): void
    {
        foreach ($order->getOrderProducts() as $line) {
            $product = $line->getProduct();
            $qty = (int) $line->getQte();
            if (!$product || $qty <= 0) {
                continue;
            }
            $product->setStock((int) $product->getStock() + $qty);
        }
        $this->em->flush();
    }

    /** Stock déjà réservé pour Wave/OM/COD, ou après paiement Stripe. */
    public function wasReserved(Order $order): bool
    {
        return $order->isManualPayment() || (bool) $order->isPaymentCompleted();
    }
}
