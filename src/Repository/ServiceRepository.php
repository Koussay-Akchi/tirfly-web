<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }
 // src/Repository/ServiceRepository.php

 public function findWithFilters(
    ?string $searchTerm = null,
    ?int $hebergementId = null,
    ?string $priceSort = null
): array {
    $qb = $this->createQueryBuilder('s')
        ->leftJoin('s.hebergement', 'h');

    if ($searchTerm) {
        $qb->andWhere('s.nom LIKE :searchTerm OR s.description LIKE :searchTerm')
           ->setParameter('searchTerm', '%' . $searchTerm . '%');
    }

    if ($hebergementId) {
        $qb->andWhere('h.id = :hebergementId')
           ->setParameter('hebergementId', $hebergementId);
    }

    if ($priceSort === 'asc') {
        $qb->orderBy('s.prix', 'ASC');
    } elseif ($priceSort === 'desc') {
        $qb->orderBy('s.prix', 'DESC');
    }

    return $qb->getQuery()->getResult();
}
    //    /**
    //     * @return Service[] Returns an array of Service objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Service
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}