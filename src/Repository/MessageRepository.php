<?php
// src/Repository/MessageRepository.php
namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findClientsForSupport(): array
    {
        return $this->createQueryBuilder('m')
            ->select('DISTINCT c')
            ->join('m.client', 'c')
            ->getQuery()
            ->getResult();
    }

    public function findChatWithClient($clientId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.client = :client')
            ->setParameter('client', $clientId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
