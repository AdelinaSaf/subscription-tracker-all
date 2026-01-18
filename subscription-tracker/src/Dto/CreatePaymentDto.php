<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePaymentDto
{
    #[Assert\NotBlank]
    public int $subscriptionId;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public float $amount;

    #[Assert\NotBlank]
    public int $statusId;
}
