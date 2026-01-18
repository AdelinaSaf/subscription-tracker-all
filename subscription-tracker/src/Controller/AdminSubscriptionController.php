<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SubscriptionService;
use App\Repository\SubscriptionRepository;

#[Route('/api/admin')]
class AdminSubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository
    ) {}

    #[Route('/subscriptions', name: 'admin_subscriptions_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Получаем все подписки с пользователями
        $subscriptions = $this->subscriptionRepository->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->orderBy('s.nextPaymentDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json(array_map(fn($s) => [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'user' => $s->getUser()->getEmail(),
            'price' => $s->getPrice(),
            'currency' => $s->getCurrency(),
            'periodicity' => $s->getPeriodicity(),
            'active' => $s->isActive(),
            'nextPaymentDate' => $s->getNextPaymentDate()->format('Y-m-d'),
            'startDate' => $s->getStartDate()->format('Y-m-d'),
            'createdAt' => $s->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $subscriptions));
    }

    #[Route('/subscriptions/{id}', name: 'admin_subscriptions_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $subscription = $this->subscriptionRepository->find($id);
        
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found'], 404);
        }

        return $this->json([
            'id' => $subscription->getId(),
            'name' => $subscription->getName(),
            'user' => [
                'id' => $subscription->getUser()->getId(),
                'email' => $subscription->getUser()->getEmail(),
                'timezone' => $subscription->getUser()->getTimezone(),
            ],
            'price' => $subscription->getPrice(),
            'currency' => $subscription->getCurrency(),
            'periodicity' => $subscription->getPeriodicity(),
            'active' => $subscription->isActive(),
            'nextPaymentDate' => $subscription->getNextPaymentDate()->format('Y-m-d'),
            'startDate' => $subscription->getStartDate()->format('Y-m-d'),
            'failedPaymentAttempts' => $subscription->getFailedPaymentAttempts(),
            'createdAt' => $subscription->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $subscription->getUpdatedAt() ? $subscription->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ]);
    }

    #[Route('/subscriptions/{id}', name: 'admin_subscriptions_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $subscription = $this->subscriptionRepository->find($id);
        
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found'], 404);
        }

        $this->subscriptionRepository->remove($subscription, true);

        return $this->json(['message' => 'Subscription deleted successfully']);
    }

    #[Route('/subscriptions/{id}/toggle', name: 'admin_subscriptions_toggle', methods: ['PATCH'])]
    public function toggleStatus(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $subscription = $this->subscriptionRepository->find($id);
        
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found'], 404);
        }

        $subscription->setActive(!$subscription->isActive());
        $this->subscriptionRepository->save($subscription, true);

        return $this->json([
            'message' => 'Subscription status updated',
            'active' => $subscription->isActive()
        ]);
    }
}