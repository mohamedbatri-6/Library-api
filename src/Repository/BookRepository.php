<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save(Book $book, bool $flush = true): void
    {
        $this->getEntityManager()->persist($book);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTitle(string $title): ?Book
    {
        return $this->findOneBy(['title' => $title]);
    }

    public function findAvailableBooks(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.isBorrowed = :isBorrowed')
            ->setParameter('isBorrowed', false)
            ->getQuery()
            ->getResult();
    }

    public function findByAuthor(string $author): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getResult();
    }

    public function searchBooks(string $searchTerm): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.title LIKE :search')
            ->orWhere('b.author LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();
    }
}