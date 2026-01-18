<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserService;

#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    public function __construct(private UserService $userService) {}

    #[Route('/users', name: 'admin_users_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $this->userService->listAll();
        return $this->json(array_map(fn($u) => [
            'id' => $u->getId(),
            'email' => $u->getEmail(),
            'roles' => $u->getRoles(),
            'timezone' => $u->getTimezone(),
        ], $users));
    }

    #[Route('/users/{id}', name: 'admin_users_edit', methods: ['PATCH'])]
    public function editUser(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        
        // Сначала найдем пользователя
        $users = $this->userService->listAll();
        $user = null;
        foreach ($users as $u) {
            if ($u->getId() === $id) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Обновим данные
        if (isset($data['timezone'])) {
            $this->userService->updateTimezone($user, $data['timezone']);
        }
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
            $this->userService->updateUser($user);
        }
        
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
            $this->userService->updateUser($user);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'timezone' => $user->getTimezone()
        ]);
    }

    #[Route('/users/{id}', name: 'admin_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Для примера удаляем админа
        try {
            $this->userService->deleteAdmin($id);
            return $this->json(['message' => 'User deleted']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}