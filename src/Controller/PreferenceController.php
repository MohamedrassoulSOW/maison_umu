<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\ProductPersonalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PreferenceController extends AbstractController
{
    #[Route('/preferences/view/{id}', name: 'app_preference_view', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function view(
        int $id,
        ProductRepository $productRepository,
        ProductPersonalizer $personalizer,
        SessionInterface $session,
    ): JsonResponse {
        $product = $productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['ok' => false], 404);
        }

        $personalizer->rememberProduct($session, $product, 1);

        return new JsonResponse(['ok' => true]);
    }
}
