<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function findAllFeedbacks(): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.id, f.contenu, f.dateFeedback, f.note')
            ->getQuery()
            ->getResult();
    }
}
