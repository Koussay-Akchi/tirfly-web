<?php

namespace App\Repository;

use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    public function findByClient($client)
    {
        return $this->createQueryBuilder('r')
            ->join('r.reclamation', 'rec')
            ->where('rec.client = :client')
            ->setParameter('client', $client)
            ->orderBy('r.dateReponse', 'DESC')
            ->getQuery()
            ->getResult();
    }
}