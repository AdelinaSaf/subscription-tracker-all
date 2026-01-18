<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Subscription;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('0 9 * * *', 'daily_reminders')]
class NotificationService
{
    private NotificationRepository $repository;
    private EntityManagerInterface $em;
    private SubscriptionService $subscriptionService;

    public function __construct(
        NotificationRepository $repository,
        EntityManagerInterface $em,
        SubscriptionService $subscriptionService
    ) {
        $this->repository = $repository;
        $this->em = $em;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Создает системное уведомление
     */
    public function createSystemNotification(
        User $user,
        string $message,
        ?Subscription $subscription = null
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setSubscription($subscription);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTime());
        $notification->setType('system');

        $this->repository->save($notification, true);

        return $notification;
    }


    public function createPaymentNotification(
        User $user,
        string $message,
        Subscription $subscription,
        float $amount
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setSubscription($subscription);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTime());
        $notification->setType('payment');
        $notification->setAmount($amount);

        $this->repository->save($notification, true);

        return $notification;
    }


    public function scheduleNotification(
        User $user,
        string $message,
        ?Subscription $subscription = null,
        \DateTimeInterface $scheduledFor
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setSubscription($subscription);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTime());
        $notification->setScheduledFor($scheduledFor);
        $notification->setType('scheduled');

        $this->repository->save($notification, true);
    }


    public function sendDailyReminders(): void
    {
        $upcomingSubscriptions = $this->subscriptionService->getSubscriptionsDueInDays(3);
        
        foreach ($upcomingSubscriptions as $subscription) {
            $user = $subscription->getUser();
            $amount = $subscription->getPrice();
            
            $this->createPaymentNotification(
                $user,
                sprintf('Через 3 дня списание по подписке "%s" - %s %s',
                    $subscription->getName(),
                    $amount,
                    $subscription->getCurrency()
                ),
                $subscription,
                $amount
            );
        }

        // Получаем подписки с платежом завтра
        $tomorrowSubscriptions = $this->subscriptionService->getSubscriptionsDueInDays(1);
        
        foreach ($tomorrowSubscriptions as $subscription) {
            $user = $subscription->getUser();
            $amount = $subscription->getPrice();
            
            $this->createPaymentNotification(
                $user,
                sprintf('Завтра списание по подписке "%s" - %s %s',
                    $subscription->getName(),
                    $amount,
                    $subscription->getCurrency()
                ),
                $subscription,
                $amount
            );
        }
    }


    public function sendPaymentSuccessNotification(
        User $user,
        Subscription $subscription,
        float $amount
    ): void {
        $this->createPaymentNotification(
            $user,
            sprintf('Платеж по подписке "%s" прошел успешно. Списано: %s %s',
                $subscription->getName(),
                $amount,
                $subscription->getCurrency()
            ),
            $subscription,
            $amount
        );
    }


    public function sendPaymentFailedNotification(
        User $user,
        Subscription $subscription,
        float $amount,
        string $reason = ''
    ): void {
        $message = sprintf('Ошибка платежа по подписке "%s"', $subscription->getName());
        
        if ($reason) {
            $message .= sprintf('. Причина: %s', $reason);
        }

        $this->createPaymentNotification(
            $user,
            $message,
            $subscription,
            $amount
        );
    }


    public function markAsRead(User $user, int $id): void
    {
        $notification = $this->repository->find($id);

        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException();
        }

        $this->repository->markAsRead($notification, true);
    }


    public function markAllAsRead(User $user): void
    {
        $this->repository->markAllAsReadForUser($user, true);
    }


    public function getUnreadNotifications(User $user): array
    {
        return $this->repository->findUnreadByUser($user);
    }


    public function getAllNotifications(User $user): array
    {
        return $this->repository->findByUser($user);
    }


    public function deleteNotification(User $user, int $id): void
    {
        $notification = $this->repository->findByIdAndUser($id, $user);

        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException();
        }

        $this->repository->remove($notification, true);
    }


    public function getAllNotificationsForAdmin(): array
    {
        return $this->repository->findAllForAdmin();
    }
}