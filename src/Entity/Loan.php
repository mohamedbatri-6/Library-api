<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "loans")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\Column(type: "datetime")]
    private DateTime $borrowedAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $returnedAt = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $lateFee = null;

    public function __construct()
    {
        $this->borrowedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): self
    {
        $this->book = $book;
        return $this;
    }

    public function getBorrowedAt(): DateTime
    {
        return $this->borrowedAt;
    }

    public function setBorrowedAt(DateTime $borrowedAt): self
    {
        $this->borrowedAt = $borrowedAt;
        return $this;
    }

    public function getReturnedAt(): ?DateTime
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?DateTime $returnedAt): self
    {
        $this->returnedAt = $returnedAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->returnedAt === null;
    }

    public function getLateFee(): ?float
    {
        return $this->lateFee;
    }

    public function setLateFee(?float $lateFee): self
    {
        $this->lateFee = $lateFee;
        return $this;
    }

    public function markAsReturned(): self
    {
        $this->returnedAt = new DateTime();
        $this->calculateLateFee();
        return $this;
    }

    private function calculateLateFee(): void
    {
        $daysLimit = 14; // 14 jours de prêt maximum
        $feePerDay = 0.50; // 0.50€ par jour de retard

        $interval = $this->borrowedAt->diff($this->returnedAt);
        $daysElapsed = $interval->days;

        if ($daysElapsed <= $daysLimit) {
            $this->lateFee = 0;
            return;
        }

        $this->lateFee = ($daysElapsed - $daysLimit) * $feePerDay;
    }
}