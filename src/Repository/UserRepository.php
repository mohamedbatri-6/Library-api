<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByName(string $name): ?User
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function getUsersWithOverdueBooks(): array
    {
        $qb = $this->createQueryBuilder('u');
        
        return $qb->join('u.loans', 'l')
            ->where('l.returnedAt IS NULL')
            ->andWhere('l.borrowedAt < :twoWeeksAgo')
            ->setParameter('twoWeeksAgo', new \DateTime('-14 days'))
            ->getQuery()
            ->getResult();
    }
}