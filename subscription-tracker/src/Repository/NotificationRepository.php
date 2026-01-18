<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

public function findByUser(User $user, array $orderBy = ['createdAt' => 'DESC']): array
    {
        return $this->findBy(['user' => $user], $orderBy);
    }

    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.user', 'u')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByIdAndUser(int $id, User $user): ?Notification
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function save(Notification $notification, bool $flush = true): void
    {
        $this->_em->persist($notification);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function markAsRead(Notification $notification, bool $flush = true): void
    {
        $notification->setIsRead(true);
        
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function markAllAsReadForUser(User $user, bool $flush = true): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('isRead', true)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function remove(Notification $notification, bool $flush = true): void
    {
        $this->_em->remove($notification);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
