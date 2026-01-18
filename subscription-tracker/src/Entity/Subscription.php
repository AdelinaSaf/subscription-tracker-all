<?php

// src/Entity/Subscription.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\SubscriptionRepository")]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 10)]
    private ?string $currency = null;

    #[ORM\Column(length: 20)]
    private ?string $periodicity = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $nextPaymentDate = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $failedPaymentAttempts = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastPaymentDate = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $lastPaymentAmount = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'boolean')]
    private bool $autoRenew = true;

    // Конструктор с автоматической установкой дат
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Геттеры и сеттеры
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getPeriodicity(): ?string
    {
        return $this->periodicity;
    }

    public function setPeriodicity(string $periodicity): self
    {
        $this->periodicity = $periodicity;
        return $this;
    }

    public function getNextPaymentDate(): ?\DateTimeInterface
    {
        return $this->nextPaymentDate;
    }

    public function setNextPaymentDate(string|\DateTimeInterface $nextPaymentDate): self
    {
        if (is_string($nextPaymentDate)) {
            $nextPaymentDate = new \DateTime($nextPaymentDate);
        }
        $this->nextPaymentDate = $nextPaymentDate;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(string|\DateTimeInterface $startDate): self
    {
        if (is_string($startDate)) {
            $startDate = new \DateTime($startDate);
        }
        $this->startDate = $startDate;
        return $this;
    }
    public function getFailedPaymentAttempts(): int
    {
        return $this->failedPaymentAttempts;
    }

    public function setFailedPaymentAttempts(int $failedPaymentAttempts): self
    {
        $this->failedPaymentAttempts = $failedPaymentAttempts;
        return $this;
    }

    public function getLastPaymentDate(): ?\DateTimeInterface
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate(?\DateTimeInterface $lastPaymentDate): self
    {
        $this->lastPaymentDate = $lastPaymentDate;
        return $this;
    }

    public function getLastPaymentAmount(): ?float
    {
        return $this->lastPaymentAmount;
    }

    public function setLastPaymentAmount(?float $lastPaymentAmount): self
    {
        $this->lastPaymentAmount = $lastPaymentAmount;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function isAutoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew): self
    {
        $this->autoRenew = $autoRenew;
        return $this;
    }
}