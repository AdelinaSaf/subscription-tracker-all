<?php

namespace App\Service;

use App\Entity\PaymentHistory;
use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\Status;
use App\Repository\PaymentHistoryRepository;
use App\Repository\StatusRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use App\Service\NotificationService;

#[AsCronTask('0 0 * * *', 'daily_payments')]
class PaymentHistoryService
{
    private PaymentHistoryRepository $paymentRepository;
    private StatusRepository $statusRepository;
    private SubscriptionRepository $subscriptionRepository;
    private EntityManagerInterface $em;
    private NotificationService $notificationService;

    public function __construct(
        PaymentHistoryRepository $paymentRepository,
        StatusRepository $statusRepository,
        SubscriptionRepository $subscriptionRepository,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->statusRepository = $statusRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->em = $em;
        $this->notificationService = $notificationService;
    }


    public function createPayment(
        User $user,
        int $subscriptionId,
        float $amount,
        int $statusId,
        ?\DateTimeInterface $paymentDate = null
    ): PaymentHistory {
        $subscription = $this->subscriptionRepository->find($subscriptionId);
        $status = $this->statusRepository->find($statusId);

        if (!$subscription) {
            throw new \InvalidArgumentException('Subscription not found');
        }

        if (!$status) {
            throw new \InvalidArgumentException('Status not found');
        }

        // Проверяем права пользователя
        if ($subscription->getUser()->getId() !== $user->getId() && !$user->isAdmin()) {
            throw new AccessDeniedException('You cannot create payment for this subscription');
        }

        $payment = new PaymentHistory();
        $payment->setUser($user);
        $payment->setSubscription($subscription);
        $payment->setAmount($amount);
        $payment->setStatus($status);
        $payment->setPaymentDate($paymentDate ?: new \DateTime());

        $this->paymentRepository->save($payment, true);
        $this->sendPaymentNotification($payment);

        return $payment;
    }


    public function processDuePayments(): void
    {
        $dueSubscriptions = $this->subscriptionRepository->findDueTodaySubscriptions();

        foreach ($dueSubscriptions as $subscription) {
            try {
                $this->processSubscriptionPayment($subscription);
            } catch (\Exception $e) {
                $this->handlePaymentError($subscription, $e->getMessage());
            }
        }
    }


    private function processSubscriptionPayment(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        $amount = $subscription->getPrice();
        
        // Здесь будет интеграция с платежной системой
        // Для примера, будем считать все платежи успешными
        
        $status = $this->statusRepository->findOneBy(['name' => 'Успешно']);
        
        $payment = new PaymentHistory();
        $payment->setUser($user);
        $payment->setSubscription($subscription);
        $payment->setAmount($amount);
        $payment->setStatus($status);
        $payment->setPaymentDate(new \DateTime());

        $this->paymentRepository->save($payment, false);
        $this->updateNextPaymentDate($subscription);
        $this->subscriptionRepository->save($subscription, true);

        $this->notificationService->sendPaymentSuccessNotification($user, $subscription, $amount);
    }


    private function updateNextPaymentDate(Subscription $subscription): void
    {
        $nextPaymentDate = clone $subscription->getNextPaymentDate();
        
        switch ($subscription->getPeriodicity()) {
            case 'month':
                $nextPaymentDate->modify('+1 month');
                break;
            case 'year':
                $nextPaymentDate->modify('+1 year');
                break;
            case 'week':
                $nextPaymentDate->modify('+1 week');
                break;
            default:
                throw new \InvalidArgumentException('Invalid periodicity');
        }

        $subscription->setNextPaymentDate($nextPaymentDate);
    }


    private function handlePaymentError(Subscription $subscription, string $errorMessage): void
    {
        $user = $subscription->getUser();
        $amount = $subscription->getPrice();
        
        $status = $this->statusRepository->findOneBy(['name' => 'Ошибка']);
        
        $payment = new PaymentHistory();
        $payment->setUser($user);
        $payment->setSubscription($subscription);
        $payment->setAmount($amount);
        $payment->setStatus($status);
        $payment->setPaymentDate(new \DateTime());
        $payment->setNotes($errorMessage);

        $this->paymentRepository->save($payment, false);
        $this->handleFailedPaymentAttempt($subscription);
        $this->subscriptionRepository->save($subscription, true);

        $this->notificationService->sendPaymentFailedNotification(
            $user,
            $subscription,
            $amount,
            $errorMessage
        );
    }


    private function handleFailedPaymentAttempt(Subscription $subscription): void
    {
        $failedAttempts = $subscription->getFailedPaymentAttempts() + 1;
        $subscription->setFailedPaymentAttempts($failedAttempts);

        // После 3 неудачных попыток деактивируем подписку
        if ($failedAttempts >= 3) {
            $subscription->setActive(false);
        }
    }

    /**
     * Получает историю платежей пользователя
     */
    public function getUserPayments(User $user, array $filters = []): array
    {
       return $this->paymentRepository->findByUser($user, $filters);
    }

    
    /**
     * Отправляет уведомление о платеже
     */
    private function sendPaymentNotification(PaymentHistory $payment): void
    {
        $user = $payment->getUser();
        $subscription = $payment->getSubscription();
        $status = $payment->getStatus();
        $amount = $payment->getAmount();

        if ($status->getName() === 'Успешно') {
            $this->notificationService->sendPaymentSuccessNotification(
                $user,
                $subscription,
                $amount
            );
        } else {
            $this->notificationService->sendPaymentFailedNotification(
                $user,
                $subscription,
                $amount,
                $payment->getNotes()
            );
        }
    }

    /**
     * Для администратора: получает все платежи
     */
    public function getAllPayments(array $filters = []): array
    {
        return $this->paymentRepository->findAllWithFilters($filters);
    }

    
    public function updatePaymentStatus(int $paymentId, int $statusId, ?string $notes = null): PaymentHistory
    {
        $payment = $this->paymentRepository->find($paymentId);
        $status = $this->statusRepository->find($statusId);

        if (!$payment) {
            throw new \InvalidArgumentException('Payment not found');
        }

        if (!$status) {
            throw new \InvalidArgumentException('Status not found');
        }

        $oldStatus = $payment->getStatus();
        $payment->setStatus($status);
        
        if ($notes) {
            $payment->setNotes($notes);
        }

        $this->paymentRepository->save($payment, true);

        // Если статус изменился, отправляем уведомление
        if ($oldStatus->getId() !== $status->getId()) {
            $this->sendPaymentNotification($payment);
        }

        return $payment;
    }


    public function deletePayment(int $paymentId, User $user): void
    {
        $payment = $this->paymentRepository->find($paymentId);

        if (!$payment) {
            throw new \InvalidArgumentException('Payment not found');
        }

        // Проверяем права
        if ($payment->getUser()->getId() !== $user->getId() && !$user->isAdmin()) {
            throw new AccessDeniedException();
        }

        $this->paymentRepository->remove($payment, true);
    }

    public function updatePayment(PaymentHistory $payment, UpdatePaymentDto $dto): PaymentHistory
    {
        if ($dto->statusId !== null) {
            $status = $this->statusRepository->find($dto->statusId);
            if (!$status) {
                throw new \InvalidArgumentException('Status not found');
            }
            $payment->setStatus($status);
            
            // Отправляем уведомление об изменении статуса
            $this->sendPaymentStatusChangeNotification($payment);
        }

        if ($dto->amount !== null) {
            $payment->setAmount($dto->amount);
        }

        if ($dto->currency !== null) {
            $payment->setCurrency($dto->currency);
        }

        if ($dto->paymentDate !== null) {
            $payment->setPaymentDate(new \DateTime($dto->paymentDate));
        }

        if ($dto->notes !== null) {
            $payment->setNotes($dto->notes);
        }

        if ($dto->transactionId !== null) {
            $payment->setTransactionId($dto->transactionId);
        }

        if ($dto->metadata !== null) {
            $payment->setMetadata($dto->metadata);
        }

        $payment->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return $payment;
    }

    /**
     * Повторная обработка платежа
     */
    public function retryPayment(PaymentHistory $failedPayment): array
    {
        if ($failedPayment->getStatus()->getName() !== 'Ошибка') {
            throw new \InvalidArgumentException('Can only retry failed payments');
        }

        $subscription = $failedPayment->getSubscription();
        $user = $failedPayment->getUser();

        if (!$subscription->isActive()) {
            throw new \InvalidArgumentException('Subscription is not active');
        }

        try {
            $this->processSubscriptionPayment($subscription);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'originalPaymentId' => $failedPayment->getId()
            ];
        } catch (\Exception $e) {
            $subscription->setFailedPaymentAttempts(
                $subscription->getFailedPaymentAttempts() + 1
            );
            
            if ($subscription->getFailedPaymentAttempts() >= 3) {
                $subscription->setActive(false);
                $this->notificationService->createSystemNotification(
                    $user,
                    sprintf('Подписка "%s" деактивирована после 3 неудачных платежей', 
                        $subscription->getName()
                    ),
                    $subscription
                );
            }
            
            $this->subscriptionRepository->save($subscription, true);
            
            return [
                'success' => false,
                'message' => 'Payment retry failed: ' . $e->getMessage(),
                'originalPaymentId' => $failedPayment->getId()
            ];
        }
    }

    /**
     * Экспорт платежей
     */
    public function exportPayments(array $filters, string $format = 'csv'): array
    {
        $payments = $this->paymentRepository->exportData($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($payments);
            default:
                throw new \InvalidArgumentException('Unsupported format: ' . $format);
        }
    }

    /**
     * Экспорт в CSV
     */
    private function exportToCsv(array $payments): array
    {
        $csv = [];
        
        // Заголовки
        $csv[] = [
            'ID',
            'Дата',
            'Пользователь',
            'Подписка',
            'Сумма',
            'Валюта',
            'Статус',
            'Примечания',
            'ID транзакции',
            'Дата создания'
        ];
        
        // Данные
        foreach ($payments as $payment) {
            $csv[] = [
                $payment->getId(),
                $payment->getPaymentDate()->format('d.m.Y'),
                $payment->getUser()->getEmail(),
                $payment->getSubscription()->getName(),
                $payment->getAmount(),
                $payment->getCurrency(),
                $payment->getStatus()->getName(),
                $payment->getNotes() ?: '',
                $payment->getTransactionId() ?: '',
                $payment->getCreatedAt()->format('d.m.Y H:i')
            ];
        }
        
        // Конвертируем в строку CSV
        $csvString = '';
        foreach ($csv as $row) {
            $csvString .= implode(',', array_map(function($item) {
                return '"' . str_replace('"', '""', $item) . '"';
            }, $row)) . "\n";
        }
        
        return [
            'content' => $csvString,
            'contentType' => 'text/csv',
            'filename' => 'payments_' . date('Y-m-d_H-i-s') . '.csv'
        ];
    }

    /**
     * Получение статистики платежей
     */
    public function getPaymentStatistics(array $filters, string $period = 'month'): array
    {
        return $this->paymentRepository->getStatistics($filters);
    }

    /**
     * Отправляет уведомление об изменении статуса платежа
     */
    private function sendPaymentStatusChangeNotification(PaymentHistory $payment): void
    {
        $user = $payment->getUser();
        $subscription = $payment->getSubscription();
        $status = $payment->getStatus()->getName();
        $amount = $payment->getAmount();
        $currency = $payment->getCurrency();
        
        $message = sprintf(
            'Статус платежа по подписке "%s" изменен на "%s". Сумма: %s %s',
            $subscription->getName(),
            $status,
            $amount,
            $currency
        );
        
        $this->notificationService->createPaymentNotification(
            $user,
            $message,
            $subscription,
            $amount
        );
    }
}