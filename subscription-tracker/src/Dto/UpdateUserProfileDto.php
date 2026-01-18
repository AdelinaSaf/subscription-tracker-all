<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserProfileDto
{
    #[Assert\Timezone]
    public string $timezone;
}
