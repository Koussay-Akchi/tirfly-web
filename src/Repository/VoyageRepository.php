<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voyage>
 */
class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    public function filterVoyages($search, $dateDepart, $dateArrive, $country)
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.destination', 'd');

        if ($search) {
            $qb->andWhere('v.nom LIKE :search')
                ->setParameter('search', "%$search%");
        }

        if ($dateDepart) {
            $qb->andWhere('v.dateDepart >= :dateDepart')
                ->setParameter('dateDepart', new \DateTime($dateDepart));
        }

        if ($dateArrive) {
            $qb->andWhere('v.dateArrive <= :dateArrive')
                ->setParameter('dateArrive', new \DateTime($dateArrive));
        }

        if ($country) {
            $qb->andWhere('d.pays = :country')
                ->setParameter('country', $country);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllWithPrice()
{
    return $this->createQueryBuilder('v')
        ->select('v.id, v.nom, v.prix')
        ->getQuery()
        ->getResult();
}
    //    /**
    //     * @return Voyage[] Returns an array of Voyage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Voyage
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
