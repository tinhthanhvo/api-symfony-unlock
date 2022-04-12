<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Product $entity, bool $flush = true): void
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
    public function remove(Product $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param array $param
     * @param $orderBy
     * @param $limit
     * @param $offset
     * @return array
     */
    public function findByConditions(array $param, $orderBy, $limit, $offset): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.deleteAt IS NULL');

        if (isset($param['priceFrom']) && $param['priceFrom'] != '') {
            $queryBuilder
                ->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $param['priceFrom']);
        }

        if (isset($param['name']) && $param['name'] != '') {
            $param['name'] = '%' . $param['name'] . '%';
            $queryBuilder
                ->andWhere('p.name LIKE :name')
                ->setParameter('name', $param['name']);
        }

        if (isset($param['priceTo']) && $param['priceTo'] != '') {
            $queryBuilder
                ->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $param['priceTo']);
        }

        if (isset($param['category']) && $param['category'] != 0) {
            $queryBuilder
                ->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $param['category']);
        }

        if (isset($param['color']) && count($param['color']) > 0) {
            $queryBuilder
                ->andWhere('p.color IN (:colorList)')
                ->setParameter('colorList', $param['color']);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $orderType) {
                if ($field == 'inStock') {
                    $queryBuilder
                        ->addSelect('(
                                SELECT SUM(pi.amount) 
                                FROM App\Entity\ProductItem pi 
                                WHERE p.id = pi.product 
                                GROUP BY pi.product
                            ) AS sum_amount')
                        ->addOrderBy('sum_amount', $orderType);
                } else {
                    $queryBuilder->addOrderBy('p.' . $field, $orderType);
                }
            }
        }

        $products = $queryBuilder->getQuery()->getScalarResult();

        $productPerPage = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->execute();

        if (isset($orderBy['inStock'])) {
            $realData = [];
            foreach ($productPerPage as $rowData) {
                $realData[] = $rowData[0];
            }
            $productPerPage = $realData;
        }

        return ['data' => $productPerPage, 'total' => count($products)];
    }

    /*
    public function findOneBySomeField($value): ?Product
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
