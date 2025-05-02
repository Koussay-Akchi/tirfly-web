<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    // Add custom query methods here if needed
    // Example:
    /*
    public function findByVoyage($voyageId)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.voyage = :voyage')
            ->setParameter('voyage', $voyageId)
            ->orderBy('f.dateFeedback', 'DESC')
            ->getQuery()
            ->getResult();
    }
    */
}