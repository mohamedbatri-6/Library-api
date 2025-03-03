<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotFoundException;
use App\Exception\TooManyBooksException;
use App\Exception\UserNotFoundException;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class LoanService
{
    private BookRepository $bookRepository;
    private UserRepository $userRepository;
    private LoanRepository $loanRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BookRepository $bookRepository,
        UserRepository $userRepository,
        LoanRepository $loanRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->bookRepository = $bookRepository;
        $this->userRepository = $userRepository;
        $this->loanRepository = $loanRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Borrow a book
     *
     * @throws BookNotFoundException
     * @throws UserNotFoundException
     * @throws BookAlreadyBorrowedException
     * @throws TooManyBooksException
     */
    public function borrowBook(string $title, int $userId): Loan
    {
        // Récupération des entités
        $book = $this->bookRepository->findByTitle($title);
        if (!$book) {
            throw new BookNotFoundException("Le livre avec le titre '{$title}' n'a pas été trouvé.");
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException("L'utilisateur avec l'ID {$userId} n'a pas été trouvé.");
        }

        // Vérification si le livre est déjà emprunté
        if ($book->isBorrowed()) {
            throw new BookAlreadyBorrowedException("Le livre '{$title}' est déjà emprunté.");
        }

        // Vérification si l'utilisateur peut emprunter plus de livres
        if (!$user->canBorrowBooks()) {
            throw new TooManyBooksException("L'utilisateur a déjà emprunté le nombre maximum de livres autorisés.");
        }

        // Création du prêt
        $loan = new Loan();
        $loan->setUser($user);
        $loan->setBook($book);
        
        // Mise à jour du livre
        $book->markAsBorrowed();
        
        // Sauvegarde
        $this->loanRepository->save($loan);
        $this->bookRepository->save($book);

        return $loan;
    }

    /**
     * Return a book
     * 
     * @throws BookNotFoundException
     * @throws UserNotFoundException
     */
    public function returnBook(string $title, int $userId): Loan
    {
        // Récupération des entités
        $book = $this->bookRepository->findByTitle($title);
        if (!$book) {
            throw new BookNotFoundException("Le livre avec le titre '{$title}' n'a pas été trouvé.");
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException("L'utilisateur avec l'ID {$userId} n'a pas été trouvé.");
        }

        // Recherche du prêt actif
        $loan = $this->loanRepository->findActiveLoanByUserAndBook($user, $book);
        if (!$loan) {
            throw new \Exception("Ce livre n'a pas été emprunté par cet utilisateur.");
        }

        // Mise à jour du prêt et du livre
        $loan->markAsReturned();
        $book->markAsReturned();
        
        // Sauvegarde
        $this->loanRepository->save($loan);
        $this->bookRepository->save($book);

        return $loan;
    }

    /**
     * Get all active loans
     */
    public function getActiveLoans(): array
    {
        return $this->loanRepository->findBy(['returnedAt' => null]);
    }

    /**
     * Get overdue loans
     */
    public function getOverdueLoans(): array
    {
        return $this->loanRepository->findOverdueLoans();
    }

    /**
     * Get active loans for a user
     */
    public function getActiveLoansForUser(User $user): array
    {
        return $this->loanRepository->findActiveByUser($user);
    }
}