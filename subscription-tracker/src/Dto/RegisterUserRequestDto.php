<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegisterUserRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Expression(
        expression: 'this.getPassword() === this.getPasswordConfirm()',
        message: 'Passwords do not match'
    )]
    public string $passwordConfirm;

    #[Assert\Timezone]
    public ?string $timezone = 'UTC';

    #[Assert\Callback]
    public function validatePasswordStrength(ExecutionContextInterface $context): void
    {
        $password = $this->password;

        // Простейшие правила:
        // - минимум одна буква, одна цифра, один спецсимвол
        // - не "password", "123456", "qwerty"
        $commonPasswords = ['password', '123456', '123456789', 'qwerty', '111111'];

        if (in_array(strtolower($password), $commonPasswords, true)) {
            $context->buildViolation('Password is too common')
                ->atPath('password')
                ->addViolation();
        }

        if (!preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/\d/', $password) ||
            !preg_match('/[\W_]/', $password)) {
            $context->buildViolation('Password must contain letters, numbers, and special characters')
                ->atPath('password')
                ->addViolation();
        }
    }


    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirm(): string
    {
        return $this->passwordConfirm;
    }
}

