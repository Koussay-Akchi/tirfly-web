<?php

namespace App\Repository;

use App\Entity\Hebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hebergement>
 */
class HebergementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hebergement::class);
    }

    public function createSearchQueryBuilder(
        ?string $query,
        ?string $type,
        ?int $destinationId,
        ?float $maxPrice,
        ?int $minRating,
        array $amenities,
        ?string $sort
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.destination', 'd')
            ->leftJoin('h.services', 's');

        // Text search
        if ($query) {
            $qb->andWhere('h.nom LIKE :query OR h.description LIKE :query OR d.ville LIKE :query OR d.pays LIKE :query')
               ->setParameter('query', '%'.$query.'%');
        }

        // Type filter
        if ($type) {
            switch ($type) {
                case 'hotel':
                    $qb->andWhere('SIZE(h.hotels) > 0');
                    break;
                case 'logement':
                    $qb->andWhere('SIZE(h.logements) > 0');
                    break;
                case 'foyer':
                    $qb->andWhere('SIZE(h.foyers) > 0');
                    break;
            }
        }

        // Destination filter
        if ($destinationId) {
            $qb->andWhere('d.id = :destinationId')
               ->setParameter('destinationId', $destinationId);
        }

        // Price filter
        if ($maxPrice) {
            // Join with first hotel/logement/foyer using subqueries
            $qb->leftJoin('h.hotels', 'hotel')
               ->leftJoin('h.logements', 'logement')
               ->leftJoin('h.foyers', 'foyer')
               ->andWhere('
                   (hotel.id IS NOT NULL AND hotel.prix <= :maxPrice) OR
                   (logement.id IS NOT NULL AND logement.prix <= :maxPrice) OR
                   (foyer.id IS NOT NULL AND foyer.frais <= :maxPrice) OR
                   (SIZE(h.hotels) = 0 AND SIZE(h.logements) = 0 AND SIZE(h.foyers) = 0)
               ')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Rating filter
        if ($minRating) {
            $qb->andWhere('h.classement >= :minRating')
               ->setParameter('minRating', $minRating);
        }

        // Amenities filter
        if (!empty($amenities)) {
            $qb->andWhere('s.id IN (:amenities)')
               ->setParameter('amenities', $amenities)
               ->groupBy('h.id')
               ->having('COUNT(s.id) = :amenitiesCount')
               ->setParameter('amenitiesCount', count($amenities));
        }

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $qb->leftJoin('h.hotels', 'sort_hotel')
                   ->leftJoin('h.logements', 'sort_logement')
                   ->leftJoin('h.foyers', 'sort_foyer')
                   ->orderBy('
                       CASE 
                           WHEN SIZE(h.hotels) > 0 THEN sort_hotel.prix
                           WHEN SIZE(h.logements) > 0 THEN sort_logement.prix
                           WHEN SIZE(h.foyers) > 0 THEN sort_foyer.frais
                           ELSE 999999
                       END', 'ASC');
                break;
            case 'price_desc':
                $qb->leftJoin('h.hotels', 'sort_hotel')
                   ->leftJoin('h.logements', 'sort_logement')
                   ->leftJoin('h.foyers', 'sort_foyer')
                   ->orderBy('
                       CASE 
                           WHEN SIZE(h.hotels) > 0 THEN sort_hotel.prix
                           WHEN SIZE(h.logements) > 0 THEN sort_logement.prix
                           WHEN SIZE(h.foyers) > 0 THEN sort_foyer.frais
                           ELSE 0
                       END', 'DESC');
                break;
            case 'rating_desc':
                $qb->orderBy('h.classement', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('h.nom', 'ASC');
                break;
            default:
                $qb->orderBy('h.id', 'DESC');
        }

        return $qb;
    }

    public function findAllDestinations(): array
    {
        return $this->createQueryBuilder('h')
            ->select('DISTINCT d.id, d.ville, d.pays')
            ->leftJoin('h.destination', 'd')
            ->andWhere('d.id IS NOT NULL')
            ->orderBy('d.pays', 'ASC')
            ->addOrderBy('d.ville', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllServices(): array
    {
        return $this->createQueryBuilder('h')
            ->select('DISTINCT s.id, s.nom')
            ->leftJoin('h.services', 's')
            ->andWhere('s.id IS NOT NULL')
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
}