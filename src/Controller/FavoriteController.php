<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProductRepository;
use App\Service\Favorite;
use App\Service\ProductPersonalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly Favorite $favorite,
        private readonly ProductPersonalizer $personalizer,
    ) {
    }

    #[Route('/favorites', name: 'app_favorites', methods: ['GET'])]
    public function index(SessionInterface $session): Response
    {
        $user = $this->getUser();
        $userEntity = $user instanceof User ? $user : null;

        return $this->render('favorite/index.html.twig', [
            'products' => $this->favorite->getProducts($session, $userEntity),
        ]);
    }

    #[Route('/favorites/toggle/{id}', name: 'app_favorite_toggle', requirements: ['id' => '\d+'], methods: ['POST', 'GET'])]
    public function toggle(int $id, Request $request, SessionInterface $session): Response
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            if ($this->wantsJson($request)) {
                return new JsonResponse(['ok' => false, 'message' => 'Produit introuvable.'], 404);
            }

            throw $this->createNotFoundException('Produit introuvable.');
        }

        $user = $this->getUser();
        $userEntity = $user instanceof User ? $user : null;
        $result = $this->favorite->toggle($session, $product, $userEntity);
        if ($result['liked']) {
            $this->personalizer->rememberProduct($session, $product, 4);
        }

        if ($this->wantsJson($request)) {
            return new JsonResponse([
                'ok' => true,
                'liked' => $result['liked'],
                'likesCount' => $result['likesCount'],
                'count' => $result['favoritesCount'],
                'productId' => $id,
            ]);
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_favorites');
    }

    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
    }
}
