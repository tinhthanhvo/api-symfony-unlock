<?php

namespace App\Repository;

use App\Entity\ProductItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductItem[]    findAll()
 * @method ProductItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductItem::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(ProductItem $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ProductItem $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param int|null $product_id
     * @return array
     */
    public function getDataForReport(?int $product_id): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.deleteAt IS NULL')
            ->leftJoin('p.product', 'pr')
            ->leftJoin('pr.category', 'c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('pr.id', 'ASC')
            ->addOrderBy('pr.color', 'ASC')
            ->addOrderBy('p.size', 'ASC');

        if (!empty($product_id)) {
            $queryBuilder->andWhere('p.product = :product')
                ->setParameter('product', $product_id);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    // /**
    //  * @return ProductItem[] Returns an array of ProductItem objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductItem
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
