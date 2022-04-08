<?php

namespace App\Repository;

use App\Entity\OrderDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderDetail[]    findAll()
 * @method OrderDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderDetail::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(OrderDetail $entity, bool $flush = true): void
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
    public function remove(OrderDetail $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function clear(): void
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
    public function getListDateOrder(?int $product_id): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.deleteAt IS NULL')
            ->orderBy('o.createAt', 'ASC');

        if (!empty($product_id)) {
            $queryBuilder->innerJoin('o.productItem', 'pi', 'WITH', 'pi.product = :product')
                ->setParameter('product', $product_id);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $param
     * @return array
     */
    public function sumOrderDetailData(array $param): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.amount) AS sum_quantity')
            ->groupBy('o.productItem')
            ->addSelect('SUM(o.price) AS sum_amount')
            ->groupBy('o.productItem')
            ->andWhere('o.deleteAt IS NULL')
            ->andWhere('o.productItem = :product_item_id')
            ->andWhere('o.createAt LIKE :order_date')
            ->setParameters($param)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return OrderDetail[] Returns an array of OrderDetail objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OrderDetail
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
