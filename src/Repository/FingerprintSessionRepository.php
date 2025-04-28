<?php
namespace App\Repository;

use App\Entity\FingerprintSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FingerprintSession>
 *
 * @method FingerprintSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method FingerprintSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method FingerprintSession[]    findAll()
 * @method FingerprintSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FingerprintSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FingerprintSession::class);
    }

    /**
     * Find a fingerprint session by token.
     *
     * @param string $token
     * @return FingerprintSession|null
     */
    public function findOneByToken(string $token): ?FingerprintSession
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Remove a fingerprint session.
     *
     * @param FingerprintSession $session
     */
    public function remove(FingerprintSession $session): void
    {
        $this->getEntityManager()->remove($session);
        $this->getEntityManager()->flush();
    }

    /**
     * Save a fingerprint session.
     *
     * @param FingerprintSession $session
     */
    public function save(FingerprintSession $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }

    /**
     * Find expired sessions (optional, for future cleanup tasks).
     *
     * @return FingerprintSession[]
     */
    public function findExpiredSessions(): array
    {
        return $this->createQueryBuilder('fs')
            ->where('fs.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}