<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdatePaymentDto
{
    #[Assert\Positive]
    public ?int $statusId = null;

    #[Assert\Positive]
    #[Assert\Range(min: 0.01, max: 1000000)]
    public ?float $amount = null;

    #[Assert\Length(min: 3, max: 3)]
    #[Assert\Choice(['RUB', 'USD', 'EUR', 'GBP', 'CNY'])]
    public ?string $currency = null;

    #[Assert\Date]
    public ?string $paymentDate = null;

    #[Assert\Length(max: 500)]
    public ?string $notes = null;

    #[Assert\Length(max: 100)]
    public ?string $transactionId = null;

    public ?array $metadata = null;
}