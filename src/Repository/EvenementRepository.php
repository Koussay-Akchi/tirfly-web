<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }
  

public function findPaginated(int $page, int $limit): array
{
    $query = $this->createQueryBuilder('e')
        ->orderBy('e.dateDebut', 'DESC')
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
public function findPaginatedWithFilters(
    int $page, 
    int $limit,
    ?string $search = null,
    ?string $dateDebut = null,
    ?string $dateFin = null,
    ?string $destination = null
): array {
    $query = $this->createQueryBuilder('e')
        ->leftJoin('e.destination', 'd');

    if ($search) {
        $query->andWhere('e.titre LIKE :search')
            ->setParameter('search', '%'.$search.'%');
    }

    if ($dateDebut) {
        $query->andWhere('e.dateDebut >= :dateDebut')
            ->setParameter('dateDebut', new \DateTime($dateDebut));
    }

    if ($dateFin) {
        $query->andWhere('e.dateFin <= :dateFin')
            ->setParameter('dateFin', new \DateTime($dateFin));
    }

    if ($destination) {
        $query->andWhere('d.id = :destination')
            ->setParameter('destination', $destination);
    }

    $query->orderBy('e.dateDebut', 'DESC');

    $paginator = new Paginator($query);
    $total = count($paginator);
    $pages = ceil($total / $limit);

    $query->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

    return [
        'results' => $query->getQuery()->getResult(),
        'current_page' => $page,
        'max_per_page' => $limit,
        'total_pages' => $pages,
        'total_items' => $total
    ];
}

public function findUniqueDestinations(): array
{
    return $this->createQueryBuilder('e')
        ->select('d.id, d.ville, d.pays')
        ->leftJoin('e.destination', 'd')
        ->groupBy('d.id')
        ->getQuery()
        ->getResult();
}
    //    /**
    //     * @return Evenement[] Returns an array of Evenement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Evenement
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
