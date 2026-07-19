<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductLike>
 */
class ProductLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductLike::class);
    }

    public function findOneByProductAndVisitor(Product $product, string $visitorKey): ?ProductLike
    {
        return $this->findOneBy([
            'product' => $product,
            'visitorKey' => $visitorKey,
        ]);
    }

    /**
     * @return list<int>
     */
    public function findProductIdsByVisitor(string $visitorKey): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.product) AS productId')
            ->andWhere('l.visitorKey = :key')
            ->setParameter('key', $visitorKey)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_map(static fn (array $row): int => (int) $row['productId'], $rows));
    }

    public function countByProduct(Product $product): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<ProductLike>
     */
    public function findByVisitorKey(string $visitorKey): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.visitorKey = :key')
            ->setParameter('key', $visitorKey)
            ->getQuery()
            ->getResult();
    }
}
