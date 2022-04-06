<?php

namespace App\Repository;

use App\Entity\PurchaseOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PurchaseOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurchaseOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurchaseOrder[]    findAll()
 * @method PurchaseOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurchaseOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseOrder::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PurchaseOrder $entity, bool $flush = true): void
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
    public function remove(PurchaseOrder $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findByConditions(array $param, $orderBy, $limit, $offset): array
    {
        $queryBuilder = $this->createQueryBuilder('o');

        if (isset($param['status']) && $param['status'] != 0) {
            $queryBuilder
                ->andWhere('o.status = :status')
                ->setParameter('status', $param['status']);
        }

        if (!empty($orderBy)) {
            $keyOrderList = array_keys($orderBy);
            $column = 'o.' . $keyOrderList[0];
            $valueSort = $orderBy[$keyOrderList[0]];
            $queryBuilder
                ->orderBy($column, $valueSort);
        }

        $purchaseOrders = $queryBuilder->getQuery()->getScalarResult();

        $purchaseOrdersPerPage = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->execute();

        return ['data' => $purchaseOrdersPerPage, 'total' => count($purchaseOrders)];
    }

    // /**
    //  * @return PurchaseOrder[] Returns an array of PurchaseOrder objects
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
    public function findOneBySomeField($value): ?PurchaseOrder
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
