<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
final class DashboardController extends AbstractController
{
    #[Route('/editor', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        CityRepository $cityRepository,
    ): Response {
        $pendingDelivery = $orderRepository->findBy(
            ['isComplted' => null, 'payOnDelivery' => 1, 'isPaymentCompleted' => 0],
            ['id' => 'DESC'],
            5
        );
        $pendingPaid = $orderRepository->findBy(
            ['isComplted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1],
            ['id' => 'DESC'],
            5
        );

        $lowStock = $productRepository->createQueryBuilder('p')
            ->where('p.stock <= :limit')
            ->setParameter('limit', 5)
            ->orderBy('p.stock', 'ASC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        $recentOrders = $orderRepository->findBy([], ['id' => 'DESC'], 6);

        $pendingCodCount = $orderRepository->count([
            'isComplted' => null,
            'payOnDelivery' => 1,
            'isPaymentCompleted' => 0,
        ]);
        $pendingPaidCount = $orderRepository->count([
            'isComplted' => null,
            'payOnDelivery' => 0,
            'isPaymentCompleted' => 1,
        ]);
        $stripePendingCount = $orderRepository->count([
            'paymentMethod' => 'stripe',
            'isPaymentCompleted' => 0,
            'isComplted' => null,
        ]);

        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'products' => $productRepository->count([]),
                'orders' => $orderRepository->count([]),
                'pending' => $pendingCodCount + $pendingPaidCount + $stripePendingCount,
                'pendingCod' => $pendingCodCount,
                'pendingPaid' => $pendingPaidCount,
                'stripePending' => $stripePendingCount,
                'users' => $userRepository->count([]),
                'categories' => $categoryRepository->count([]),
                'cities' => $cityRepository->count([]),
                'delivered' => $orderRepository->count(['isComplted' => 1]),
                'lowStock' => count($lowStock),
            ],
            'pendingDelivery' => $pendingDelivery,
            'pendingPaid' => $pendingPaid,
            'lowStockProducts' => $lowStock,
            'recentOrders' => $recentOrders,
        ]);
    }
}
