<?php

namespace App\Controller;

use App\Dto\CreateNotificationDto;
use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/notifications')]
final class NotificationController extends AbstractController
{
    public function __construct(private NotificationService $service) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $notifications = $user->getNotifications();

        return $this->json(array_map(fn($n) => [
            'id' => $n->getId(),
            'message' => $n->getMessage(),
            'subscription' => $n->getSubscription()?->getName(),
            'isRead' => $n->isIsRead(),
            'createdAt' => $n->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $notifications->toArray()));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new CreateNotificationDto();
        $dto->message = $data['message'] ?? '';
        $dto->subscriptionId = $data['subscriptionId'] ?? null;

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        $user = $this->getUser();

        $notification = $this->service->create($user, $dto);

        return $this->json([
            'id' => $notification->getId(),
            'message' => $notification->getMessage(),
            'isRead' => $notification->isIsRead(),
            'subscription' => $notification->getSubscription()?->getName(),
            'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }

    #[Route('/{id}/read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        $this->service->markAsRead($user, $id);

        return $this->json(['message' => 'Notification marked as read']);
    }
}
