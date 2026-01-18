<?php

namespace App\Controller;

use App\Dto\CreateSubscriptionDto;
use App\Dto\UpdateSubscriptionDto;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Enum\RolesEnum;

#[Route('/api/subscriptions')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionService $service,
        private ValidatorInterface $validator
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('ROLE_USER');

        $onlyActive = $request->query->get('onlyActive', 'true') === 'true';
    
        $subs = $this->service->list($user, $onlyActive);

        return $this->json(array_map(fn($s) => [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'price' => $s->getPrice(),
            'currency' => $s->getCurrency(),
            'periodicity' => $s->getPeriodicity(),
            'nextPaymentDate' => $s->getNextPaymentDate()->format('Y-m-d'),
            'startDate' => $s->getStartDate() ? $s->getStartDate()->format('Y-m-d') : null,
            'active' => $s->isActive(),
            'createdAt' => $s->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $subs));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        
        // Создаем DTO
        $dto = new CreateSubscriptionDto();
        $dto->name = $data['name'] ?? '';
        $dto->price = $data['price'] ?? 0;
        $dto->currency = $data['currency'] ?? 'USD';
        $dto->periodicity = $data['periodicity'] ?? 'month';
        $dto->nextPaymentDate = $data['nextPaymentDate'] ?? date('Y-m-d');
        $dto->startDate = $data['startDate'] ?? null;

        // Валидация
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        try {
            $sub = $this->service->create($user, $dto);
            return $this->json([
                'message' => 'Subscription created',
                'id' => $sub->getId(),
                'data' => [
                    'id' => $sub->getId(),
                    'name' => $sub->getName(),
                    'price' => $sub->getPrice(),
                    'currency' => $sub->getCurrency(),
                    'periodicity' => $sub->getPeriodicity(),
                    'nextPaymentDate' => $sub->getNextPaymentDate()->format('Y-m-d'),
                    'startDate' => $sub->getStartDate()->format('Y-m-d'),
                    'active' => $sub->isActive(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateSubscriptionDto();
        $dto->name = $data['name'] ?? null;
        $dto->price = $data['price'] ?? null;
        $dto->currency = $data['currency'] ?? null;
        $dto->periodicity = $data['periodicity'] ?? null;
        $dto->nextPaymentDate = $data['nextPaymentDate'] ?? null;
        $dto->startDate = $data['startDate'] ?? null;
        $dto->active = $data['active'] ?? null;

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        try {
            $sub = $this->service->update($user, $id, $dto);
            return $this->json([
                'message' => 'Subscription updated',
                'id' => $sub->getId(),
                'data' => [
                    'name' => $sub->getName(),
                    'price' => $sub->getPrice(),
                    'currency' => $sub->getCurrency(),
                    'periodicity' => $sub->getPeriodicity(),
                    'nextPaymentDate' => $sub->getNextPaymentDate()->format('Y-m-d'),
                    'startDate' => $sub->getStartDate() ? $sub->getStartDate()->format('Y-m-d') : null,
                    'active' => $sub->isActive(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/toggle-status', methods: ['POST'])]
    public function toggleStatus(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        try {
            $sub = $this->service->toggleStatus($user, $id);
            return $this->json([
                'message' => 'Subscription status toggled',
                'id' => $sub->getId(),
                'data' => [
                    'active' => $sub->isActive(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to toggle subscription status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        try {
            $this->service->delete($user, $id);
            return $this->json(['message' => 'Subscription deleted']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
