<?php

namespace App\Service;

use App\Dto\CreateSubscriptionDto;
use App\Dto\UpdateSubscriptionDto;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $repository,
    ) {}

    public function list(User $user, bool $onlyActive = true): array
    {
        return $this->repository->findByUser($user, $onlyActive);
    }

    public function get(User $user, int $id): Subscription
    {
        $subscription = $this->repository->findOneByIdAndUser($id, $user);

        if (!$subscription) {
            throw new AccessDeniedException();
        }

        return $subscription;
    }

    public function create(User $user, CreateSubscriptionDto $dto): Subscription
    {
        $nextPaymentDate = new \DateTime($dto->nextPaymentDate);
        $today = new \DateTime('today');
        
        if ($nextPaymentDate < $today) {
            throw new \InvalidArgumentException('Дата следующего платежа не может быть в прошлом');
        }
        
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setName($dto->name);
        $subscription->setPrice($dto->price);
        $subscription->setCurrency($dto->currency);
        $subscription->setPeriodicity($dto->periodicity);
        $subscription->setActive(true);
        $subscription->setNextPaymentDate($nextPaymentDate);
        $subscription->setStartDate($dto->startDate ?? new \DateTime());

        $this->repository->save($subscription, true);

        return $subscription;
    }

    public function update(
        User $user,
        int $id,
        UpdateSubscriptionDto $dto
    ): Subscription {
        $subscription = $this->get($user, $id);

        if ($dto->name !== null) {
            $subscription->setName($dto->name);
        }

        if ($dto->price !== null) {
            $subscription->setPrice($dto->price);
        }

        if ($dto->currency !== null) {
            $subscription->setCurrency($dto->currency);
        }

        if ($dto->periodicity !== null) {
            $subscription->setPeriodicity($dto->periodicity);
        }

        if ($dto->nextPaymentDate !== null) {
            $nextPaymentDate = new \DateTime($dto->nextPaymentDate);
            $today = new \DateTime('today');
            
            if ($nextPaymentDate < $today) {
                throw new \InvalidArgumentException('Дата следующего платежа не может быть в прошлом');
            }
            
            $subscription->setNextPaymentDate($nextPaymentDate);
        }

        if ($dto->active !== null) {
            $subscription->setActive($dto->active);
        }
        
        $subscription->setUpdatedAt(new \DateTime());
        $this->repository->save($subscription, true);

        return $subscription;
    }

    public function toggleStatus(User $user, int $id): Subscription
    {
        $subscription = $this->get($user, $id);
        $subscription->setActive(!$subscription->isActive());
        $subscription->setUpdatedAt(new \DateTime());
        
        $this->repository->save($subscription, true);

        return $subscription;
    }

    public function delete(User $user, int $id): void
    {
        $subscription = $this->get($user, $id);
        $this->repository->remove($subscription, true);
    }

    public function getSubscriptionsDueInDays(int $days): array
    {
        return $this->repository->findSubscriptionsDueInDays($days);
    }
}