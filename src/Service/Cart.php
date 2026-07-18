<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{
    public function getCart(SessionInterface $session, ProductRepository $productRepository): array
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $dirty = false;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if (!$product || (int) $quantity <= 0) {
                unset($cart[$id]);
                $dirty = true;
                continue;
            }

            $cartWithData[] = [
                'product' => $product,
                'quantity' => (int) $quantity,
            ];
        }

        if ($dirty) {
            $session->set('cart', $cart);
        }

        $total = array_sum(array_map(
            static fn (array $item): float => (float) $item['product']->getPrice() * $item['quantity'],
            $cartWithData
        ));

        return [
            'cart' => $cartWithData,
            'total' => $total,
        ];
    }
}
