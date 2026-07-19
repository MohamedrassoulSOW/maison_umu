<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Cart;
use App\Service\ProductPersonalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductPersonalizer $personalizer,
    ) {
    }

    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(SessionInterface $session, Cart $cart): Response
    {
        $data = $cart->getCart($session, $this->productRepository);

        return $this->render('cart/index.html.twig', [
            'items' => $data['cart'],
            'total' => $data['total'],
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_new', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function addToCart(int $id, SessionInterface $session): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        if ((int) $product->getStock() <= 0) {
            $this->addFlash('danger', 'Ce produit est actuellement en rupture de stock.');

            return $this->redirectToRoute('app_home_product_show', ['id' => $id]);
        }

        $cart = $session->get('cart', []);
        $currentQty = (int) ($cart[$id] ?? 0);

        if ($currentQty + 1 > (int) $product->getStock()) {
            $this->addFlash('danger', 'Stock insuffisant pour ajouter davantage de ce produit.');

            return $this->redirectToRoute('app_cart');
        }

        $cart[$id] = $currentQty + 1;
        $session->set('cart', $cart);
        $this->personalizer->rememberProduct($session, $product, 3);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/increase/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function increaseFromCart(int $id, SessionInterface $session): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $cart = $session->get('cart', []);
        $currentQty = (int) ($cart[$id] ?? 0);

        if ($currentQty + 1 > (int) $product->getStock()) {
            $this->addFlash('danger', 'Stock insuffisant pour ce produit.');

            return $this->redirectToRoute('app_cart');
        }

        $cart[$id] = $currentQty + 1;
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/add/remove/{id}/', name: 'app_cart_product_remove', methods: ['GET'])]
    public function removeFromCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/add/remove', name: 'app_cart_remove', methods: ['GET'])]
    public function clearCart(SessionInterface $session): Response
    {
        $session->remove('cart');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{id}', name: 'app_cart_decrease', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function decreaseFromCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (array_key_exists($id, $cart)) {
            $cart[$id]--;
            if ($cart[$id] <= 0) {
                unset($cart[$id]);
            }
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }
}
