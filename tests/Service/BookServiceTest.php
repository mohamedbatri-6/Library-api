<?php

namespace App\Tests\Service;

use App\Entity\Book;
use App\Exception\BookNotFoundException;
use App\Repository\BookRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BookServiceTest extends TestCase
{
    private $bookRepository;
    private $entityManager;
    private $bookService;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->bookService = new BookService(
            $this->bookRepository,
            $this->entityManager
        );
    }

    public function testCreateBook()
    {
        // Arrange
        $title = "Le Petit Prince";
        $author = "Antoine de Saint-Exupéry";
        
        $this->bookRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Book $book) {
                $this->assertEquals("Le Petit Prince", $book->getTitle());
                $this->assertEquals("Antoine de Saint-Exupéry", $book->getAuthor());
            });
        
        // Act
        $result = $this->bookService->createBook($title, $author);
        
        // Assert
        $this->assertInstanceOf(Book::class, $result);
        $this->assertEquals($title, $result->getTitle());
        $this->assertEquals($author, $result->getAuthor());
    }

    public function testFindBookByTitleSuccess()
    {
        // Arrange
        $title = "Le Petit Prince";
        $book = new Book();
        $book->setTitle($title);
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn($book);
        
        // Act
        $result = $this->bookService->findBookByTitle($title);
        
        // Assert
        $this->assertSame($book, $result);
    }

    public function testFindBookByTitleThrowsExceptionWhenNotFound()
    {
        // Arrange
        $title = "Livre inexistant";
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByTitle')
            ->with($title)
            ->willReturn(null);
        
        // Assert & Act
        $this->expectException(BookNotFoundException::class);
        $this->bookService->findBookByTitle($title);
    }

    public function testGetAvailableBooks()
    {
        // Arrange
        $book1 = new Book();
        $book1->setTitle("Livre 1");
        
        $book2 = new Book();
        $book2->setTitle("Livre 2");
        
        $books = [$book1, $book2];
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findAvailableBooks')
            ->willReturn($books);
        
        // Act
        $result = $this->bookService->getAvailableBooks();
        
        // Assert
        $this->assertSame($books, $result);
    }
}