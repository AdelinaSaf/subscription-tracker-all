<?php

namespace App\Service;

use App\Dto\RegisterUserRequestDto;
use App\Entity\User;
use App\Enum\RolesEnum;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;


class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {}

    public function registerUser(RegisterUserRequestDto $dto, UserPasswordHasherInterface $hasher): User
    {
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new \DomainException("Email already exists");
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setTimezone($dto->timezone ?? 'UTC');
        $user->setRoles([RolesEnum::ROLE_USER]);
        $user->setPassword(
            $hasher->hashPassword($user, $dto->password)
        );
        $this->userRepository->save($user);

        return $user;
    }

    public function updateTimezone(User $user, string $timezone): void
    {
        $user->setTimezone($timezone);
        $this->userRepository->save($user, true);
    }

    public function listAll(): array
    {
        return $this->userRepository->findAll();
    }

    public function listAdmins(): array
    {
        return $this->userRepository->findAdmins();
    }

    public function createAdmin(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([RolesEnum::ROLE_ADMIN->value]);
        $hashed = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashed);
        $this->userRepository->save($user);
        return $user;
    }

    public function deleteAdmin(int $id): void
    {
        $user = $this->userRepository->find($id);
        if ($user && in_array(RolesEnum::ROLE_ADMIN->value, $user->getRoles())) {
            $this->userRepository->remove($user);
        }
    }
}

