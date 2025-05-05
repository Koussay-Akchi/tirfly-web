<?php

namespace App\Repository;

use App\Entity\Panier;
use App\Entity\Client;
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
    

    public function findActivePanierForUser(Client $client): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.articles', 'a')  // Join with articles
            ->addSelect('a')               // Select articles
            ->leftJoin('a.produit', 'pr')  // Join with products
            ->addSelect('pr')              // Select products
            ->where('p.client = :user')
            ->andWhere('p.etat = :state')
            ->setParameter('user', $client)
            ->setParameter('state', 'active')
            ->getQuery()
            ->getOneOrNullResult();
    }
}