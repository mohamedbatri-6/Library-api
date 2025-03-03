<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    public function save(Loan $loan, bool $flush = true): void
    {
        $this->getEntityManager()->persist($loan);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveByBook(Book $book): ?Loan
    {
        return $this->createQueryBuilder('l')
            ->where('l.book = :book')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('book', $book)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findActiveLoanByUserAndBook(User $user, Book $book): ?Loan
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.book = :book')
            ->andWhere('l.returnedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('book', $book)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOverdueLoans(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.returnedAt IS NULL')
            ->andWhere('l.borrowedAt < :twoWeeksAgo')
            ->setParameter('twoWeeksAgo', new \DateTime('-14 days'))
            ->getQuery()
            ->getResult();
    }
}