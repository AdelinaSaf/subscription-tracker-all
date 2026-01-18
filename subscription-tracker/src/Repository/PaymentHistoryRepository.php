<?php

namespace App\Repository;

use App\Entity\PaymentHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentHistory>
 */
class PaymentHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentHistory::class);
    }


    // public function find(int $id): ?PaymentHistory
    // {
    //     return parent::find($id);
    // }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.paymentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->orderBy('p.paymentDate', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    public function getStatistics(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p');
        $this->applyFilters($qb, $filters);

        // Общая статистика
        $totalQuery = clone $qb;
        $totalResult = $totalQuery->select('COUNT(p.id) as count')
            ->getQuery()
            ->getSingleScalarResult();

        // Статистика по статусам
        $statusQuery = clone $qb;
        $statusResult = $statusQuery->select([
                'COUNT(p.id) as count',
                's.name as status'
            ])
            ->join('p.status', 's')
            ->groupBy('s.name')
            ->getQuery()
            ->getResult();

        // Другие статистические запросы...

        return [
            'total' => (int)$totalResult,
            'byStatus' => array_column($statusResult, 'count', 'status'),
        ];
    }

    public function exportData(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->join('p.subscription', 's')
            ->join('p.status', 'st')
            ->orderBy('p.paymentDate', 'DESC');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    private function applyFilters($qb, array $filters): void
    {
        if (isset($filters['startDate'])) {
            $qb->andWhere('p.paymentDate >= :startDate')
               ->setParameter('startDate', $filters['startDate']);
        }

        if (isset($filters['endDate'])) {
            $qb->andWhere('p.paymentDate <= :endDate')
               ->setParameter('endDate', $filters['endDate']);
        }

        if (isset($filters['status'])) {
            $qb->join('p.status', 's')
               ->andWhere('s.name = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['userId'])) {
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $filters['userId']);
        }
    }

    public function save(PaymentHistory $payment, bool $flush = true): void
    {
        $this->_em->persist($payment);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(PaymentHistory $payment, bool $flush = true): void
    {
        $this->_em->remove($payment);

        if ($flush) {
            $this->_em->flush();
        }
    }

}
