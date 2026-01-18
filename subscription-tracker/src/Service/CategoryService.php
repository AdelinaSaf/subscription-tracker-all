<?php

namespace App\Service;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;



final class CategoryService
{
    public function __construct(
        private CategoryRepository $repository,
        private EntityManagerInterface $em
    ) {}

    public function list(): array
    {
        return $this->repository->findAll();
    }

    public function create(CreateCategoryDto $dto): Category
    {
        $category = new Category();
        $category->setName($dto->name);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }
}
