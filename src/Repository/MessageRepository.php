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

    /**
     * Find distinct users who sent messages in chats where the current user is the support
     * @return array
     */
    public function findClientsForSupport($supportUser): array
    {
        return $this->createQueryBuilder('m')
            ->select('DISTINCT u')
            ->join('m.expediteur', 'u')
            ->join('m.chat', 'c')
            ->where('c.support = :support')
            ->setParameter('support', $supportUser)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find messages for a chat involving a specific client
     * @param int $clientId
     * @return array
     */
    public function findChatWithClient($clientId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.chat', 'c')
            ->where('c.client = :client')
            ->setParameter('client', $clientId)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }
}