<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Sous-catégories des produits récemment commandés par e-mail.
     *
     * @return array<int, int> subcategoryId => occurrence count
     */
    public function findRecentSubCategoryIdsByEmail(string $email, int $orderLimit = 8): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('s.id AS subId, COUNT(s.id) AS cnt')
            ->innerJoin('o.orderProducts', 'op')
            ->innerJoin('op.product', 'p')
            ->innerJoin('p.subcategories', 's')
            ->andWhere('LOWER(o.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->groupBy('s.id')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($orderLimit * 4)
            ->getQuery()
            ->getScalarResult();

        $weights = [];
        foreach ($rows as $row) {
            $weights[(int) $row['subId']] = (int) $row['cnt'];
        }

        return $weights;
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
