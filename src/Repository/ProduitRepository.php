<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }
    // src/Repository/ProduitRepository.php

public function filterProduits(?string $search, ?string $category, ?bool $ecoFriendly): array
{
    $qb = $this->createQueryBuilder('p');

    if ($search) {
        $qb->andWhere('p.nom LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($category) {
        $qb->andWhere('p.categorie = :category')
           ->setParameter('category', $category);
    }

    if (!is_null($ecoFriendly)) {
        $qb->andWhere('p.ecoFriendly = :ecoFriendly')
           ->setParameter('ecoFriendly', $ecoFriendly);
    }

    return $qb->getQuery()->getResult();
}
public function findAllOrderedByName()
{
    return $this->createQueryBuilder('p')
        ->orderBy('p.nom', 'ASC')
        ->getQuery()
        ->getResult();
}

public function findDistinctCategories()
{
    return $this->createQueryBuilder('p')
        ->select('p.categorie')
        ->distinct()
        ->getQuery()
        ->getSingleColumnResult();
}

public function findDistinctCategoriesWithCounts(): array
{
    $results = $this->createQueryBuilder('p')
        ->select('p.categorie as name, COUNT(p.id) as count')
        ->groupBy('p.categorie')
        ->getQuery()
        ->getResult();

    $categories = [];
    foreach ($results as $result) {
        $categories[$result['name'] ?? 'Non catégorisé'] = $result['count'];
    }

    return $categories;
}

public function countEcoFriendlyProducts(): int
{
    return $this->count(['ecoFriendly' => true]);
}

public function getCategoriesWithCounts(): array
{
    $results = $this->createQueryBuilder('p')
        ->select('p.categorie as name, COUNT(p.id) as count')
        ->groupBy('p.categorie')
        ->getQuery()
        ->getResult();

    $categories = [];
    foreach ($results as $result) {
        $categoryName = $result['name'] ?? 'Non catégorisé';
        $categories[$categoryName] = $result['count'];
    }

    return $categories;
}

public function countLowStockProducts(int $threshold): int
{
    return $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->where('p.quantiteStock < :threshold')
        ->setParameter('threshold', $threshold)
        ->getQuery()
        ->getSingleScalarResult();
}
    //    /**
    //     * @return Produit[] Returns an array of Produit objects
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

    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
