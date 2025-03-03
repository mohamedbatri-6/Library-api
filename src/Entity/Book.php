<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "string", length: 255)]
    private string $author;

    #[ORM\Column(type: "boolean")]
    private bool $isBorrowed = false;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $borrowedAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $returnedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function isBorrowed(): bool
    {
        return $this->isBorrowed;
    }

    public function getBorrowedAt(): ?DateTime
    {
        return $this->borrowedAt;
    }

    public function getReturnedAt(): ?DateTime
    {
        return $this->returnedAt;
    }

    public function markAsBorrowed(): self
    {
        $this->isBorrowed = true;
        $this->borrowedAt = new DateTime();
        return $this;
    }

    public function markAsReturned(): self
    {
        $this->isBorrowed = false;
        $this->returnedAt = new DateTime();
        return $this;
    }

    public function calculateLateReturnFee(?DateTime $returnDate = null): float
    {
        if (!$this->borrowedAt) {
            return 0;
        }

        $returnDate = $returnDate ?? new DateTime();
        $daysLimit = 14; // 14 jours de prêt maximum
        $feePerDay = 0.50; // 0.50€ par jour de retard

        $interval = $this->borrowedAt->diff($returnDate);
        $daysElapsed = $interval->days;

        if ($daysElapsed <= $daysLimit) {
            return 0;
        }

        return ($daysElapsed - $daysLimit) * $feePerDay;
    }
}