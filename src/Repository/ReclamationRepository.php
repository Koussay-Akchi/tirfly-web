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

    public function findByFilters(?string $etat, ?string $urgence, ?bool $nonRepondu): \Doctrine\ORM\Query
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->orderBy('r.dateCreation', 'DESC');

        if ($etat) {
            $queryBuilder->andWhere('r.etat = :etat')
                ->setParameter('etat', $etat);
        }

        if ($urgence) {
            $queryBuilder->andWhere('r.urgence = :urgence')
                ->setParameter('urgence', $urgence);
        }

        if ($nonRepondu) {
            // Sous-requête pour identifier les réclamations avec des réponses
            $subQuery = $this->createQueryBuilder('r2')
                ->select('r2.id')
                ->leftJoin('r2.reponses', 'rep')
                ->groupBy('r2.id')
                ->having('COUNT(rep.id) > 0');

            // Exclure les réclamations avec des réponses
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('r.id', $subQuery->getDQL()));
        }

        return $queryBuilder->getQuery();
    }
}