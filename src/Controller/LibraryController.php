<?php

namespace App\Controller;

use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotFoundException;
use App\Exception\TooManyBooksException;
use App\Exception\UserNotFoundException;
use App\Service\BookService;
use App\Service\LoanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;


#[Route('/api/library', name: 'api_library_')]
class LibraryController extends AbstractController
{
    private BookService $bookService;
    private LoanService $loanService;
    private UserRepository $userRepository;

    public function __construct(
        BookService $bookService,
        LoanService $loanService,
        UserRepository $userRepository
    ) {
        $this->bookService = $bookService;
        $this->loanService = $loanService;
        $this->userRepository = $userRepository;
    }

    #[Route('/books', name: 'books_list', methods: ['GET'])]
    public function listBooks(): JsonResponse
    {
        $books = $this->bookService->getAllBooks();

        return $this->json([
            'books' => array_map(function ($book) {
                return [
                    'id' => $book->getId(),
                    'title' => $book->getTitle(),
                    'author' => $book->getAuthor(),
                    'borrowed' => $book->isBorrowed(),
                ];
            }, $books)
        ]);
    }

    #[Route('/books/available', name: 'books_available', methods: ['GET'])]
    public function listAvailableBooks(): JsonResponse
    {
        $books = $this->bookService->getAvailableBooks();

        return $this->json([
            'books' => array_map(function ($book) {
                return [
                    'id' => $book->getId(),
                    'title' => $book->getTitle(),
                    'author' => $book->getAuthor(),
                ];
            }, $books)
        ]);
    }

    #[Route('/books/search', name: 'books_search', methods: ['GET'])]
    public function searchBooks(Request $request): JsonResponse
    {
        $term = $request->query->get('term');
        
        if (!$term) {
            return $this->json(['error' => 'Un terme de recherche est requis'], 400);
        }
        
        $books = $this->bookService->searchBooks($term);
        
        return $this->json([
            'books' => array_map(function ($book) {
                return [
                    'id' => $book->getId(),
                    'title' => $book->getTitle(),
                    'author' => $book->getAuthor(),
                    'borrowed' => $book->isBorrowed(),
                ];
            }, $books)
        ]);
    }

    #[Route('/books', name: 'books_add', methods: ['POST'])]
    public function addBook(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title']) || !isset($data['author'])) {
            return $this->json(['error' => 'Le titre et l\'auteur sont requis'], 400);
        }
        
        $book = $this->bookService->createBook($data['title'], $data['author']);
        
        return $this->json([
            'message' => 'Livre ajouté avec succès',
            'book' => [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
            ]
        ], 201);
    }

    #[Route('/books/borrow', name: 'books_borrow', methods: ['POST'])]
    public function borrowBook(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title']) || !isset($data['userId'])) {
            return $this->json(['error' => 'Le titre du livre et l\'ID de l\'utilisateur sont requis'], 400);
        }
        
        try {
            $loan = $this->loanService->borrowBook($data['title'], $data['userId']);
            
            return $this->json([
                'message' => 'Livre emprunté avec succès',
                'loan' => [
                    'id' => $loan->getId(),
                    'bookTitle' => $loan->getBook()->getTitle(),
                    'userName' => $loan->getUser()->getName(),
                    'borrowedAt' => $loan->getBorrowedAt()->format('Y-m-d H:i:s'),
                ]
            ]);
            
        } catch (BookNotFoundException | UserNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (BookAlreadyBorrowedException | TooManyBooksException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/books/return', name: 'books_return', methods: ['POST'])]
    public function returnBook(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title']) || !isset($data['userId'])) {
            return $this->json(['error' => 'Le titre du livre et l\'ID de l\'utilisateur sont requis'], 400);
        }
        
        try {
            $loan = $this->loanService->returnBook($data['title'], $data['userId']);
            
            $responseData = [
                'message' => 'Livre retourné avec succès',
                'loan' => [
                    'id' => $loan->getId(),
                    'bookTitle' => $loan->getBook()->getTitle(),
                    'userName' => $loan->getUser()->getName(),
                    'borrowedAt' => $loan->getBorrowedAt()->format('Y-m-d H:i:s'),
                    'returnedAt' => $loan->getReturnedAt()->format('Y-m-d H:i:s'),
                ]
            ];
            
            if ($loan->getLateFee() > 0) {
                $responseData['lateFee'] = [
                    'amount' => $loan->getLateFee(),
                    'message' => 'Des frais de retard ont été appliqués.'
                ];
            }
            
            return $this->json($responseData);
            
        } catch (BookNotFoundException | UserNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/loans/overdue', name: 'loans_overdue', methods: ['GET'])]
    public function getOverdueLoans(): JsonResponse
    {
        $overdueLoans = $this->loanService->getOverdueLoans();
        
        return $this->json([
            'overdueLoans' => array_map(function ($loan) {
                return [
                    'id' => $loan->getId(),
                    'bookTitle' => $loan->getBook()->getTitle(),
                    'userName' => $loan->getUser()->getName(),
                    'borrowedAt' => $loan->getBorrowedAt()->format('Y-m-d H:i:s'),
                    'daysOverdue' => $loan->getBorrowedAt()->diff(new \DateTime())->days - 14,
                    'estimatedFee' => $loan->getBook()->calculateLateReturnFee(),
                ];
            }, $overdueLoans)
        ]);
    }

    #[Route('/users/{id}/loans', name: 'user_loans', methods: ['GET'])]
    public function getUserLoans(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);
            
            if (!$user) {
                throw new UserNotFoundException("L'utilisateur avec l'ID {$id} n'a pas été trouvé.");
            }
            
            $loans = $this->loanService->getActiveLoansForUser($user);
            
            return $this->json([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                ],
                'activeLoans' => array_map(function ($loan) {
                    return [
                        'id' => $loan->getId(),
                        'bookTitle' => $loan->getBook()->getTitle(),
                        'bookAuthor' => $loan->getBook()->getAuthor(),
                        'borrowedAt' => $loan->getBorrowedAt()->format('Y-m-d H:i:s'),
                    ];
                }, $loans)
            ]);
            
        } catch (UserNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }
}