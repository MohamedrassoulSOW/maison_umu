<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Classe les produits selon les signaux du visiteur :
 * favoris, panier, sous-catégories consultées, achats (email compte).
 */
class ProductPersonalizer
{
    public const SESSION_SUBCATEGORIES = 'pref_subcategories';

    public function __construct(
        private readonly Favorite $favorite,
        private readonly ProductRepository $productRepository,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    /**
     * Enregistre les sous-catégories d’un produit comme préférence (session).
     */
    public function rememberProduct(SessionInterface $session, Product $product, int $weight = 2): void
    {
        $weights = $session->get(self::SESSION_SUBCATEGORIES, []);
        foreach ($product->getSubcategories() as $sub) {
            $id = $sub->getId();
            if ($id === null) {
                continue;
            }
            $weights[$id] = ((int) ($weights[$id] ?? 0)) + $weight;
        }
        $session->set(self::SESSION_SUBCATEGORIES, $weights);
    }

    public function rememberSubCategory(SessionInterface $session, int $subCategoryId, int $weight = 3): void
    {
        $weights = $session->get(self::SESSION_SUBCATEGORIES, []);
        $weights[$subCategoryId] = ((int) ($weights[$subCategoryId] ?? 0)) + $weight;
        $session->set(self::SESSION_SUBCATEGORIES, $weights);
    }

    /**
     * @return array{products: list<Product>, personalized: bool}
     */
    public function catalog(SessionInterface $session, ?User $user = null): array
    {
        $preferred = $this->preferredSubCategoryWeights($session, $user);
        $likedIds = array_fill_keys($this->favorite->getIds($session, $user), true);
        $cartIds = array_fill_keys(array_map('intval', array_keys($session->get('cart', []))), true);

        $products = $this->productRepository->findBy([], ['id' => 'DESC']);
        $personalized = $preferred !== [];

        usort($products, function (Product $a, Product $b) use ($preferred, $likedIds, $cartIds): int {
            return $this->score($b, $preferred, $likedIds, $cartIds)
                <=> $this->score($a, $preferred, $likedIds, $cartIds)
                ?: ($b->getId() <=> $a->getId());
        });

        return [
            'products' => $products,
            'personalized' => $personalized,
        ];
    }

    /**
     * Produits proches (mêmes sous-catégories), sinon nouveautés.
     *
     * @return list<Product>
     */
    public function related(Product $product, int $limit = 5): array
    {
        $subIds = [];
        foreach ($product->getSubcategories() as $sub) {
            if ($sub->getId() !== null) {
                $subIds[] = $sub->getId();
            }
        }

        if ($subIds === []) {
            $fallback = [];
            foreach ($this->productRepository->findBy([], ['id' => 'DESC'], $limit + 1) as $candidate) {
                if ($candidate->getId() === $product->getId()) {
                    continue;
                }
                $fallback[] = $candidate;
            }

            return $fallback;
        }

        $related = $this->productRepository->findRelatedBySubCategories($product, $subIds, $limit);
        if (\count($related) >= $limit) {
            return $related;
        }

        $exclude = array_fill_keys(array_map(
            static fn (Product $p) => $p->getId(),
            [...$related, $product]
        ), true);

        foreach ($this->productRepository->findBy([], ['id' => 'DESC'], $limit * 3) as $candidate) {
            if (isset($exclude[$candidate->getId()])) {
                continue;
            }
            $related[] = $candidate;
            if (\count($related) >= $limit) {
                break;
            }
        }

        return $related;
    }

    /**
     * @return array<int, int> subcategoryId => weight
     */
    private function preferredSubCategoryWeights(SessionInterface $session, ?User $user): array
    {
        /** @var array<int|string, int> $weights */
        $weights = $session->get(self::SESSION_SUBCATEGORIES, []);

        foreach ($this->favorite->getProducts($session, $user) as $product) {
            foreach ($product->getSubcategories() as $sub) {
                $id = $sub->getId();
                if ($id === null) {
                    continue;
                }
                $weights[$id] = ((int) ($weights[$id] ?? 0)) + 4;
            }
        }

        $cart = $session->get('cart', []);
        foreach (array_keys($cart) as $productId) {
            $product = $this->productRepository->find((int) $productId);
            if (!$product) {
                continue;
            }
            foreach ($product->getSubcategories() as $sub) {
                $id = $sub->getId();
                if ($id === null) {
                    continue;
                }
                $weights[$id] = ((int) ($weights[$id] ?? 0)) + 3;
            }
        }

        if ($user?->getEmail()) {
            foreach ($this->orderRepository->findRecentSubCategoryIdsByEmail($user->getEmail(), 8) as $subId => $count) {
                $weights[$subId] = ((int) ($weights[$subId] ?? 0)) + (2 * (int) $count);
            }
        }

        $normalized = [];
        foreach ($weights as $id => $weight) {
            $normalized[(int) $id] = (int) $weight;
        }

        return $normalized;
    }

    /**
     * @param array<int, int> $preferred
     * @param array<int, true> $likedIds
     * @param array<int, true> $cartIds
     */
    private function score(Product $product, array $preferred, array $likedIds, array $cartIds): int
    {
        $score = 0;
        $id = $product->getId() ?? 0;

        foreach ($product->getSubcategories() as $sub) {
            $subId = $sub->getId();
            if ($subId !== null && isset($preferred[$subId])) {
                $score += 10 + min(20, $preferred[$subId]);
            }
        }

        if (isset($cartIds[$id])) {
            $score += 6;
        }

        // Les favoris restent visibles mais cèdent un peu la place aux similaires
        if (isset($likedIds[$id])) {
            $score -= 2;
        }

        $score += min(15, $product->getLikesCount());

        if ((int) $product->getStock() > 0) {
            $score += 5;
        }

        return $score;
    }
}
