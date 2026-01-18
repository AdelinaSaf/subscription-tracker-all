<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriptionDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public float $price;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 10)]
    public string $currency;

    #[Assert\NotBlank]
    #[Assert\Choice(['month', 'year', 'week'])]
    public string $periodicity;

    #[Assert\NotBlank]
    #[Assert\Date]
    public string $nextPaymentDate;
    
    #[Assert\Date]
    public ?string $startDate = null;
}
