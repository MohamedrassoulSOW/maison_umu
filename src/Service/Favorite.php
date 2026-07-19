<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductLike;
use App\Entity\User;
use App\Repository\ProductLikeRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Favorite
{
    public const SESSION_KEY = 'favorites';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductLikeRepository $likeRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function visitorKey(SessionInterface $session, ?User $user): string
    {
        if ($user?->getId()) {
            return 'user:'.$user->getId();
        }

        if (!$session->isStarted()) {
            $session->start();
        }

        return 'sess:'.$session->getId();
    }

    /**
     * @return list<int>
     */
    public function getIds(SessionInterface $session, ?User $user = null): array
    {
        $key = $this->visitorKey($session, $user);
        $ids = $this->likeRepository->findProductIdsByVisitor($key);

        // Garde une copie session pour le badge nav
        $session->set(self::SESSION_KEY, $ids);

        return $ids;
    }

    public function has(SessionInterface $session, int $productId, ?User $user = null): bool
    {
        return \in_array($productId, $this->getIds($session, $user), true);
    }

    public function count(SessionInterface $session, ?User $user = null): int
    {
        return \count($this->getIds($session, $user));
    }

    /**
     * @return array{liked: bool, likesCount: int, favoritesCount: int}
     */
    public function toggle(SessionInterface $session, Product $product, ?User $user = null): array
    {
        $visitorKey = $this->visitorKey($session, $user);
        $existing = $this->likeRepository->findOneByProductAndVisitor($product, $visitorKey);

        if ($existing) {
            $this->em->remove($existing);
            $product->decrementLikesCount();
            $liked = false;
        } else {
            $like = (new ProductLike())
                ->setProduct($product)
                ->setUser($user)
                ->setVisitorKey($visitorKey);
            $this->em->persist($like);
            $product->incrementLikesCount();
            $liked = true;
        }

        $this->em->flush();

        // Recalcule au besoin si le compteur a dérivé
        $realCount = $this->likeRepository->countByProduct($product);
        if ($product->getLikesCount() !== $realCount) {
            $product->setLikesCount($realCount);
            $this->em->flush();
        }

        $favoritesCount = $this->count($session, $user);

        return [
            'liked' => $liked,
            'likesCount' => $product->getLikesCount(),
            'favoritesCount' => $favoritesCount,
        ];
    }

    /**
     * @return list<Product>
     */
    public function getProducts(SessionInterface $session, ?User $user = null): array
    {
        $products = [];
        foreach ($this->getIds($session, $user) as $id) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }
}
