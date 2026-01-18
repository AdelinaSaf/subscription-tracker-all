<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }


    public function findOneByIdAndUser(int $id, User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id = :id')
            ->andWhere('s.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(User $user, bool $onlyActive = true): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.nextPaymentDate', 'ASC');

        if ($onlyActive) {
            $qb->andWhere('s.active = :active')
               ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

public function findSubscriptionsDueInDays(int $days): array
    {
        $date = new \DateTime();
        $date->modify("+{$days} days");

        return $this->createQueryBuilder('s')
            ->where('s.nextPaymentDate = :date')
            ->andWhere('s.active = true')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findDueTodaySubscriptions(): array
    {
        $today = new \DateTime();

        return $this->createQueryBuilder('s')
            ->where('s.nextPaymentDate = :today')
            ->andWhere('s.active = true')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function save(Subscription $subscription, bool $flush = true): void
    {
        $this->_em->persist($subscription);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Subscription $subscription, bool $flush = true): void
    {
        $this->_em->remove($subscription);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function deactivate(Subscription $subscription, bool $flush = true): void
    {
        $subscription->setActive(false);
        
        if ($flush) {
            $this->_em->flush();
        }
    }
}