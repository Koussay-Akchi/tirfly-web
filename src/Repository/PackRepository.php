<?php

namespace App\Repository;

use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;



/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

public function findPaginated(int $page, int $limit): array
{
    $query = $this->createQueryBuilder('p')
        ->orderBy('p.id', 'DESC')
        ->getQuery()
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

    $paginator = new Paginator($query);
    $total = count($paginator);
    $pages = ceil($total / $limit);

    return [
        'results' => $paginator,
        'current_page' => $page,
        'max_per_page' => $limit,
        'total_pages' => $pages,
        'total_items' => $total
    ];
}
    //    /**
    //     * @return Pack[] Returns an array of Pack objects
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

    //    public function findOneBySomeField($value): ?Pack
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
