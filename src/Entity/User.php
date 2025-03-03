<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    public const MAX_BOOKS_ALLOWED = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: "user", orphanRemoval: true)]
    private Collection $loans;

    public function __construct()
    {
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): self
    {
        if (!$this->loans->contains($loan)) {
            $this->loans[] = $loan;
            $loan->setUser($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): self
    {
        if ($this->loans->removeElement($loan)) {
            if ($loan->getUser() === $this) {
                $loan->setUser(null);
            }
        }

        return $this;
    }

    public function getActiveLoanCount(): int
    {
        return $this->loans->filter(function (Loan $loan) {
            return $loan->isActive();
        })->count();
    }

    public function canBorrowBooks(): bool
    {
        return $this->getActiveLoanCount() < self::MAX_BOOKS_ALLOWED;
    }

    public function getBorrowedBooks(): array
    {
        return $this->loans->filter(function (Loan $loan) {
            return $loan->isActive();
        })->map(function (Loan $loan) {
            return $loan->getBook();
        })->toArray();
    }
}