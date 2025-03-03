<?php

namespace App\Service;

use App\Entity\Book;
use App\Exception\BookNotFoundException;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookService
{
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BookRepository $bookRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->bookRepository = $bookRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Create a new book
     */
    public function createBook(string $title, string $author): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthor($author);
        
        $this->bookRepository->save($book);
        
        return $book;
    }

    /**
     * Get all available books
     */
    public function getAvailableBooks(): array
    {
        return $this->bookRepository->findAvailableBooks();
    }

    /**
     * Get all books
     */
    public function getAllBooks(): array
    {
        return $this->bookRepository->findAll();
    }

    /**
     * Find a book by its title
     * 
     * @throws BookNotFoundException
     */
    public function findBookByTitle(string $title): Book
    {
        $book = $this->bookRepository->findByTitle($title);
        
        if (!$book) {
            throw new BookNotFoundException("Le livre avec le titre '{$title}' n'a pas été trouvé.");
        }
        
        return $book;
    }

    /**
     * Find books by author
     */
    public function findBooksByAuthor(string $author): array
    {
        return $this->bookRepository->findByAuthor($author);
    }

    /**
     * Search books by title or author
     */
    public function searchBooks(string $searchTerm): array
    {
        return $this->bookRepository->searchBooks($searchTerm);
    }

    /**
     * Delete a book
     */
    public function deleteBook(Book $book): void
    {
        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }
}