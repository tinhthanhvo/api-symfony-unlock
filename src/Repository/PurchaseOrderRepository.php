<?php

namespace App\Repository;

use App\Entity\PurchaseOrder;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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

        if (isset($param['fromDate']) && $param['fromDate'] != '') {
            $queryBuilder
                ->andWhere('o.createAt >= :fromDate')
                ->setParameter('fromDate', $param['fromDate']);
        }

        if (isset($param['toDate']) && $param['toDate'] != '') {
            $queryBuilder
                ->andWhere('o.createAt <= :toDate')
                ->setParameter('toDate', $param['toDate']);
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
            ->orderBy('o.id', 'ASC');

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

    /**
     * @param DateTime|null $fromDate
     * @param DateTime|null $toDate
     * @return float|int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getRevenue(?\DateTime $fromDate = null, ?\DateTime $toDate = null)
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) as total')
            ->andWhere('o.status >= :status')
            ->setParameter('status', 4);

        if ($fromDate != '') {
            $queryBuilder
                ->andWhere('o.createAt >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        if ($toDate != '') {
            $queryBuilder
                ->andWhere('o.createAt <= :toDate')
                ->setParameter('toDate', $toDate);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult() ?? 0;
    }

    /**
     * @param DateTime|null $fromDate
     * @param DateTime|null $toDate
     * @param int $status
     * @return float|int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getCountPurchaseOrder(?\DateTime $fromDate = null, ?\DateTime $toDate = null, int $status)
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as total');

        if ($status != 0) {
            $queryBuilder
                ->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($fromDate != '') {
            $queryBuilder
                ->andWhere('o.createAt >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        if ($toDate != '') {
            $queryBuilder
                ->andWhere('o.createAt <= :toDate')
                ->setParameter('toDate', $toDate);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

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
