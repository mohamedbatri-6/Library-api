<?php

namespace App\Tests\Service;

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
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class LoanServiceTest extends TestCase
{
    private $bookRepository;
    private $userRepository;
    private $loanRepository;
    private $entityManager;
    private $loanService;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->loanService = new LoanService(
            $this->bookRepository,
            $this->userRepository,
            $this->loanRepository,
            $this->entityManager
        );
    }

    public function testBorrowBookSuccess()
    {
        // Arrange
        $title = "Le Petit Prince";
        $userId = 1;
        
        $book = new Book();
        $book->setTitle($title);
        
        $user = new User(); 
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('canBorrowBooks')->willReturn(true);
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn($book);
        
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        
        $this->loanRepository
            ->expects($this->once())
            ->method('save');
            
        $this->bookRepository
            ->expects($this->once())
            ->method('save');
        
        $result = $this->loanService->borrowBook($title, $userId);
        
        $this->assertInstanceOf(Loan::class, $result);
    }

    public function testBorrowBookThrowsExceptionWhenBookNotFound()
    {
        // Arrange
        $title = "Livre inexistant";
        $userId = 1;
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn(null);
        
        // Assert & Act
        $this->expectException(BookNotFoundException::class);
        $this->loanService->borrowBook($title, $userId);
    }

    public function testBorrowBookThrowsExceptionWhenUserNotFound()
    {
        $title = "Le Petit Prince";
        $userId = 999;
        
        $book = new Book();
        $book->setTitle($title);
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn($book);
        
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);
        
        // Assert & Act
        $this->expectException(UserNotFoundException::class);
        $this->loanService->borrowBook($title, $userId);
    }

    public function testBorrowBookThrowsExceptionWhenBookAlreadyBorrowed()
    {
        $title = "Le Petit Prince";
        $userId = 1;
        
        $book = new Book();
        $book->setTitle($title);
        $book->markAsBorrowed();
        
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('canBorrowBooks')->willReturn(true);
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn($book);
        
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        
        $this->expectException(BookAlreadyBorrowedException::class);
        $this->loanService->borrowBook($title, $userId);
    }

    public function testBorrowBookThrowsExceptionWhenUserHasTooManyBooks()
    {
        $title = "Le Petit Prince";
        $userId = 1;
        
        $book = new Book();
        $book->setTitle($title);
        
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('canBorrowBooks')->willReturn(false);
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn($book);
        
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        
        $this->expectException(TooManyBooksException::class);
        $this->loanService->borrowBook($title, $userId);
    }
}
