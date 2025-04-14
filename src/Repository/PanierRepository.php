<?php

namespace App\Repository;

use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }
    // src/Repository/PanierRepository.php

public function findActivePanierForUser($user)
{
    return $this->createQueryBuilder('p')
        ->leftJoin('p.articles', 'a')  // Join with articles
        ->addSelect('a')               // Select articles
        ->leftJoin('a.produit', 'pr')  // Join with products
        ->addSelect('pr')              // Select products
        ->where('p.client = :user')
        ->andWhere('p.etat = :state')
        ->setParameter('user', $user)
        ->setParameter('state', 'active')  // Changed from 'en cours' to 'active'
        ->getQuery()
        ->getOneOrNullResult();
}
    //    /**
    //     * @return Panier[] Returns an array of Panier objects
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

    //    public function findOneBySomeField($value): ?Panier
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
