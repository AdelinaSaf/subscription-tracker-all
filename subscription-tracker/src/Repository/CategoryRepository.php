<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Category $category, bool $flush = true): void
    {
        $this->_em->persist($category);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Category $category, bool $flush = true): void
    {
        $this->_em->remove($category);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
