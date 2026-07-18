<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\Cart;
use App\Service\OrderMailer;
use App\Service\StripePayment;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class OrderController extends AbstractController
{
    public function __construct(
        private OrderMailer $orderMailer,
        private StripePayment $stripePayment,
    ) {
    }

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Cart $cart,
    ): Response {
        $data = $cart->getCart($session, $productRepository);

        if (empty($data['cart'])) {
            $this->addFlash('danger', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart');
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($data['cart'] as $value) {
                $product = $value['product'];
                $qty = (int) $value['quantity'];
                if (!$product || $qty > (int) $product->getStock()) {
                    $this->addFlash('danger', 'Stock insuffisant pour « '.($product?->getName() ?? 'un produit').' ».');

                    return $this->redirectToRoute('app_cart');
                }
            }

            $totalPrice = $data['total'] + $order->getCity()->getShippingConst();
            $order->setTotalPrice($totalPrice);
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setIsPaymentCompleted(false);
            $entityManager->persist($order);
            $entityManager->flush();

            foreach ($data['cart'] as $value) {
                $product = $value['product'];
                $qty = (int) $value['quantity'];

                $orderProduct = new OrderProducts();
                $orderProduct->setOrder($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQte($qty);
                $entityManager->persist($orderProduct);

                $product->setStock((int) $product->getStock() - $qty);
            }
            $entityManager->flush();

            if ($order->isPayOnDelivery()) {
                $session->set('cart', []);
                try {
                    $this->orderMailer->sendOrderConfirmation($order);
                } catch (\Throwable) {
                    // Order is saved even if mail fails
                }
                $this->addFlash('success', 'Votre commande a été passée avec succès !');

                return $this->redirectToRoute('app_order-ok-message');
            }

            $session->set('pending_stripe_order_id', $order->getId());
            $this->stripePayment->startPayment($data, $order->getCity()->getShippingConst(), $order->getId());

            return $this->redirect($this->stripePayment->getStripeRedirectUrl());
        }

        return $this->render('order/index.html.twig', [
            'orderForm' => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    #[Route('/editor/order/{type}/', name: 'app_order_show')]
    #[IsGranted('ROLE_EDITOR')]
    public function getAllOrder($type, OrderRepository $orderRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = match ($type) {
            'is-Complted' => $orderRepository->findBy(['isComplted' => 1], ['id' => 'DESC']),
            'not-Complted' => $orderRepository->findBy(['isComplted' => null, 'payOnDelivery' => 1, 'isPaymentCompleted' => 0], ['id' => 'DESC']),
            'pay-on-line-not-delivered' => $orderRepository->findBy(['isComplted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']),
            'pay-on-line-is-delivered' => $orderRepository->findBy(['isComplted' => 1, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']),
            default => $orderRepository->findBy([], ['id' => 'DESC']),
        };

        $order = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('order/order_list.html.twig', [
            'orders' => $order,
            'type' => $type,
        ]);
    }

    #[Route('/editor/order/{id}/isComplted/update', name: 'app_order_isComplted_update', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function isCompltedUpdate(
        $id,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('order_complete'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $order = $orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $order->setIsComplted(true);
        $entityManager->flush();

        try {
            $this->orderMailer->sendOrderDelivered($order);
            $this->addFlash('success', 'Commande marquée comme livrée. Un email a été envoyé au client.');
        } catch (\Throwable) {
            $this->addFlash('success', 'Commande marquée comme livrée.');
            $this->addFlash('danger', 'La mise à jour est enregistrée, mais l’email n’a pas pu être envoyé.');
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    #[Route('/editor/order/{id}/remove', name: 'app_order_remove', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function removeOrder($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('order_delete'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $order = $orderRepository->find($id);
        if ($order) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'La commande a été supprimée avec succès.');
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    #[Route('/order-ok-message', name: 'app_order-ok-message')]
    public function orderMessage(): Response
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/city/{id}/shipping/const', name: 'app_city_shipping_const')]
    public function getShippingConst(City $city): JsonResponse
    {
        return new JsonResponse([
            'shippingConst' => $city->getShippingConst(),
            'message' => 'success',
            'status' => 200,
            'content' => $city->getShippingConst(),
        ]);
    }
}
