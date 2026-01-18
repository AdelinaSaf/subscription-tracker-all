<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateNotificationDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $message;

    public ?int $subscriptionId = null;
}
