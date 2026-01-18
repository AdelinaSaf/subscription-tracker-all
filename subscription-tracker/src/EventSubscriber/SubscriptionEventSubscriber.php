<?php
// src/EventSubscriber/SubscriptionEventSubscriber.php

namespace App\EventSubscriber;

use App\Entity\Subscription;
use App\Service\NotificationService;
use App\Service\PaymentService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class SubscriptionEventSubscriber implements EventSubscriber
{
    private NotificationService $notificationService;
    private PaymentService $paymentService;

    public function __construct(
        NotificationService $notificationService,
        PaymentService $paymentService
    ) {
        $this->notificationService = $notificationService;
        $this->paymentService = $paymentService;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,   // После создания
            Events::postUpdate,    // После обновления
            Events::preUpdate,     // Перед обновлением (для сравнения дат)
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Subscription) {
            $this->handleNewSubscription($entity);
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Subscription) {
            $this->handleUpdatedSubscription($entity);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Subscription) {
            // Сохраняем старую дату платежа для сравнения
            if ($args->hasChangedField('nextPaymentDate')) {
                $oldDate = $args->getOldValue('nextPaymentDate');
                $newDate = $args->getNewValue('nextPaymentDate');
                
                // Сохраняем в entityManager для использования в postUpdate
                $args->getEntityManager()->getUnitOfWork()->scheduleExtraUpdate($entity, [
                    'oldNextPaymentDate' => $oldDate
                ]);
            }
        }
    }

    private function handleNewSubscription(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        
        // Создаем уведомление о создании подписки
        $this->notificationService->createSystemNotification(
            $user,
            sprintf('Подписка "%s" создана', $subscription->getName()),
            $subscription
        );

        // Создаем первое напоминание за 3 дня до платежа
        $nextPaymentDate = clone $subscription->getNextPaymentDate();
        $reminderDate = $nextPaymentDate->modify('-3 days');
        
        if ($reminderDate > new \DateTime()) {
            $this->notificationService->scheduleNotification(
                $user,
                sprintf('Через 3 дня списание по подписке "%s"', $subscription->getName()),
                $subscription,
                $reminderDate
            );
        }
    }

    private function handleUpdatedSubscription(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        
        // Проверяем, изменилась ли дата следующего платежа
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $extraUpdates = $unitOfWork->getScheduledExtraUpdates($subscription);
        
        if (isset($extraUpdates['oldNextPaymentDate'])) {
            $oldDate = $extraUpdates['oldNextPaymentDate'];
            $newDate = $subscription->getNextPaymentDate();
            
            if ($oldDate != $newDate) {
                $this->notificationService->createSystemNotification(
                    $user,
                    sprintf('Дата платежа для "%s" изменена с %s на %s',
                        $subscription->getName(),
                        $oldDate->format('d.m.Y'),
                        $newDate->format('d.m.Y')
                    ),
                    $subscription
                );
            }
        }

        // Если подписка деактивирована
        if (!$subscription->isActive()) {
            $this->notificationService->createSystemNotification(
                $user,
                sprintf('Подписка "%s" деактивирована', $subscription->getName()),
                $subscription
            );
        }
    }
}