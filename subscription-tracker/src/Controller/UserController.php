<?php

namespace App\Controller;

use App\Dto\UpdateUserProfileDto;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/user')]
class UserController extends AbstractController
{
    public function __construct(private UserService $userService, private ValidatorInterface $validator) {}

    #[Route('/timezone', methods: ['PATCH'])]
    public function updateTimezone(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateUserProfileDto();
        $dto->timezone = $data['timezone'] ?? 'UTC';

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        $this->userService->updateTimezone($user, $dto->timezone);

        return $this->json(['message' => 'Timezone updated']);
    }

    #[Route('/me', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'timezone' => $user->getTimezone(),
        ]);
    }

    #[Route('/profile', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateUserProfileDto();
        $dto->timezone = $data['timezone'] ?? $user->getTimezone();

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        $this->userService->updateTimezone($user, $dto->timezone);

        return $this->json(['message' => 'Profile updated']);
    }

    #[Route('/password', methods: ['PATCH'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        $oldPassword = $data['oldPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        try {
            $this->userService->changePassword($user, $oldPassword, $newPassword, $hasher);
            return $this->json(['message' => 'Password changed']);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
