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
            foreach ($keyOrderList as $keyOrder) {
                $column = 'o.' . $keyOrder;
                $valueSort = $orderBy[$keyOrder];
                $queryBuilder
                    ->addOrderBy($column, $valueSort);
            }
        }

        $purchaseOrders = $queryBuilder->getQuery()->getScalarResult();

        $purchaseOrdersPerPage = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->execute();

        return ['data' => $purchaseOrdersPerPage, 'total' => count($purchaseOrders)];
    }

    /**
     * @param array $param
     * @return array
     */
    public function getDataForReport(array $param): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.deleteAt IS NULL')
            ->orderBy('o.createAt', 'ASC');

        if (isset($param['status']) && !empty($param['status'])) {
            $queryBuilder->andWhere('o.status = :status')
                ->setParameter('status', $param['status']);
        }

        if (isset($param['fromDate']) && !empty($param['fromDate'])) {
            $queryBuilder->andWhere('o.createAt >= :fromDate')
                ->setParameter('fromDate', $param['fromDate'] . ' 00:00:00');
        }

        if (isset($param['toDate']) && !empty($param['toDate'])) {
            $queryBuilder->andWhere('o.createAt <= :toDate')
                ->setParameter('toDate', $param['toDate'] . ' 23:59:59');
        }

        return $queryBuilder->getQuery()->getResult();
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
