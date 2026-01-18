<?php

namespace App\Controller;

use App\Dto\CreateCategoryDto;
use App\Entity\Category;
use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories')]
final class CategoryController extends AbstractController
{
    public function __construct(private CategoryService $service) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->service->list();

        return $this->json(array_map(fn(Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName()
        ], $categories));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new CreateCategoryDto();
        $dto->name = $data['name'] ?? '';

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        $category = $this->service->create($dto);

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName()
        ], 201);
    }
}
