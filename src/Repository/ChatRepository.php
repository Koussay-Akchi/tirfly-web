<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    /**
     * Find all chats where the user is either the client or support
     * @param User $user
     * @return Chat[]
     */
    public function findChatsForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.client = :user OR c.support = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
