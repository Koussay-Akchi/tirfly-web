<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    private function sendNewHebergementEmail(MailerInterface $mailer)
    {
        $users = $this->userRepository->findAll();
    
        foreach ($users as $user) {
            if ($user->getEmail()) { // Make sure user has email
                $email = (new Email())
                    ->from('chahd.ca@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Nouveau Hébergement Disponible')
                    ->html('<p>Un nouveau hébergement a été ajouté sur notre site ! Découvrez-le maintenant.</p>');
    
                $mailer->send($email);
            }
        }}

}
