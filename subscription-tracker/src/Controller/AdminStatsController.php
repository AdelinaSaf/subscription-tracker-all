<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PaymentHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin')]
final class AdminStatsController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private SubscriptionRepository $subscriptionRepository,
        private PaymentHistoryRepository $paymentRepository
    ) {}
    
    #[Route('/stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Получаем общую статистику
        $totalUsers = $this->userRepository->count([]);
        $totalSubscriptions = $this->subscriptionRepository->count([]);
        $activeSubscriptions = $this->subscriptionRepository->count(['active' => true]);
        
        // Получаем выручку за текущий месяц
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        $monthlyPayments = $this->paymentRepository->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->where('p.paymentDate BETWEEN :start AND :end')
            ->andWhere('p.status = :status')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->setParameter('status', 1) // Предполагаем, что 1 = успешный платеж
            ->getQuery()
            ->getSingleScalarResult();
        
        return $this->json([
            'totalUsers' => $totalUsers,
            'totalSubscriptions' => $totalSubscriptions,
            'activeSubscriptions' => $activeSubscriptions,
            'monthlyRevenue' => (float)($monthlyPayments ?? 0)
        ]);
    }
    
    #[Route('/recent-users', methods: ['GET'])]
    public function getRecentUsers(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        return $this->json(array_map(fn($u) => [
            'id' => $u->getId(),
            'email' => $u->getEmail(),
            'roles' => $u->getRoles(),
            'timezone' => $u->getTimezone(),
            'createdAt' => $u->getCreatedAt()?->format('Y-m-d H:i:s')
        ], $users));
    }
}