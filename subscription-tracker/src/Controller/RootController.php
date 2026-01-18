<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\UserService;


#[Route('/root')]
final class RootController extends AbstractController
{
    public function __construct(private UserService $userService) {}

    #[Route('/admins', methods: ['GET'])]
    public function adminsList(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ROOT');

        $admins = $this->userService->getAdmins();
        return $this->json(array_map(fn($a) => [
            'id' => $a->getId(),
            'email' => $a->getEmail(),
        ], $admins));
    }

    #[Route('/admins', methods: ['POST'])]
    public function addAdmin(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ROOT');
        $data = json_decode($request->getContent(), true);

        $admin = $this->userService->createAdmin($data['email'], $data['password']);
        return $this->json(['message' => 'Admin created', 'id' => $admin->getId()], 201);
    }

    #[Route('/admins/{id}', methods: ['DELETE'])]
    public function deleteAdmin(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ROOT');
        $this->userService->deleteAdmin($id);

        return $this->json(['message' => 'Admin deleted']);
    }
}
