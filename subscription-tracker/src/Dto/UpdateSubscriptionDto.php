<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateSubscriptionDto
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\PositiveOrZero]
    public ?float $price = null;

    #[Assert\Length(min: 3, max: 10)]
    public ?string $currency = null;

    #[Assert\Choice(['month', 'year', 'week'])]
    public ?string $periodicity = null;

    #[Assert\Date]
    public ?string $nextPaymentDate = null;
    
    #[Assert\Date]
    public ?string $startDate = null;

     #[Assert\Type('bool')]
    public ?bool $active = null;
}
