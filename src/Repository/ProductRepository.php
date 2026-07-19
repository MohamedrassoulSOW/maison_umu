<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function searchEngine(string $query){
        return $this->createQueryBuilder('p')
        ->where('p.name LIKE :query')
        ->orWhere('p.description LIKE :query')
        ->setParameter('query', '%'.$query.'%')
        ->getQuery()
        ->getResult();
    }

    /**
     * @param list<int> $subCategoryIds
     * @return list<Product>
     */
    public function findRelatedBySubCategories(Product $product, array $subCategoryIds, int $limit = 5): array
    {
        if ($subCategoryIds === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->innerJoin('p.subcategories', 's')
            ->andWhere('s.id IN (:subIds)')
            ->andWhere('p.id != :productId')
            ->setParameter('subIds', $subCategoryIds)
            ->setParameter('productId', $product->getId())
            ->orderBy('p.likesCount', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
