<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    public function getCountByEtat()
    {
        return $this->createQueryBuilder('r')
            ->select('r.etat as etat, COUNT(r.id) as count')
            ->groupBy('r.etat')
            ->getQuery()
            ->getResult();
    }

    public function findByEtat(string $etat): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.etat = :etat')
            ->setParameter('etat', $etat)
            ->orderBy('r.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(?string $etat, ?string $urgence, ?bool $nonRepondu): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.dateCreation', 'DESC'); // Add sorting by dateCreation

        if ($etat) {
            $qb->andWhere('r.etat = :etat')
               ->setParameter('etat', $etat);
        }

        if ($urgence) {
            $qb->andWhere('r.urgence = :urgence')
               ->setParameter('urgence', $urgence);
        }

        // Gestion du filtre nonRepondu
        if ($nonRepondu !== null) {
            if ($nonRepondu === true) {
                // Réclamations non répondues (aucune réponse)
                $qb->leftJoin('r.reponses', 'rep')
                   ->andWhere('rep.id IS NULL');
            } else {
                // Réclamations répondues (au moins une réponse)
                $qb->innerJoin('r.reponses', 'rep');
            }
        }

        return $qb;
    }
}