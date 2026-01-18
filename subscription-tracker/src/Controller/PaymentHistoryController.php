<?php

namespace App\Controller;

use App\Dto\CreatePaymentDto;
use App\Entity\User;
use App\Service\PaymentHistoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/payments')]
final class PaymentHistoryController extends AbstractController
{
    public function __construct(private PaymentHistoryService $service) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $payments = $this->service->getUserPayments($user);

        return $this->json(array_map(fn($p) => [
            'id' => $p->getId(),
            'subscription' => $p->getSubscription()->getName(),
            'amount' => $p->getAmount(),
            'status' => $p->getStatus()->getName(),
            'paymentDate' => $p->getPaymentDate()->format('Y-m-d'),
        ], $payments));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new CreatePaymentDto();
        $dto->subscriptionId = $data['subscriptionId'] ?? null;
        $dto->amount = $data['amount'] ?? null;
        $dto->statusId = $data['statusId'] ?? null;

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        $user = $this->getUser();

        $payment = $this->service->add(
            $user,
            $dto->subscriptionId,
            $dto->amount,
            $dto->statusId
        );

        return $this->json([
            'id' => $payment->getId(),
            'amount' => $payment->getAmount(),
            'paymentDate' => $payment->getPaymentDate()->format('Y-m-d'),
            'subscription' => $payment->getSubscription()->getName(),
            'status' => $payment->getStatus()->getName()
        ], 201);
    }
    #[Route('/{id}', methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        PaymentService $paymentService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        $dto = new UpdatePaymentDto();
        
        // Заполняем DTO
        if (isset($data['statusId'])) $dto->statusId = (int)$data['statusId'];
        if (isset($data['amount'])) $dto->amount = (float)$data['amount'];
        if (isset($data['currency'])) $dto->currency = $data['currency'];
        if (isset($data['paymentDate'])) $dto->paymentDate = $data['paymentDate'];
        if (isset($data['notes'])) $dto->notes = $data['notes'];
        if (isset($data['transactionId'])) $dto->transactionId = $data['transactionId'];
        if (isset($data['metadata'])) $dto->metadata = $data['metadata'];

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        try {
            // Проверяем права пользователя
            $payment = $this->paymentRepository->find($id);
            if (!$payment) {
                return $this->json(['error' => 'Payment not found'], 404);
            }

            // Если не админ, проверяем что платеж принадлежит пользователю
            if (!$user->isAdmin() && $payment->getUser()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            // Обновляем платеж
            $updatedPayment = $paymentService->updatePayment($payment, $dto);

            return $this->json([
                'message' => 'Payment updated',
                'payment' => [
                    'id' => $updatedPayment->getId(),
                    'amount' => $updatedPayment->getAmount(),
                    'currency' => $updatedPayment->getCurrency(),
                    'status' => $updatedPayment->getStatus()->getName(),
                    'paymentDate' => $updatedPayment->getPaymentDate()->format('Y-m-d'),
                    'notes' => $updatedPayment->getNotes(),
                    'transactionId' => $updatedPayment->getTransactionId()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/retry', methods: ['POST'])]
    public function retry(int $id, PaymentService $paymentService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        try {
            $payment = $this->paymentRepository->find($id);
            if (!$payment) {
                return $this->json(['error' => 'Payment not found'], 404);
            }

            // Проверяем права
            if (!$user->isAdmin() && $payment->getUser()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            // Повторная обработка платежа
            $result = $paymentService->retryPayment($payment);

            return $this->json([
                'message' => 'Payment retry initiated',
                'result' => $result,
                'newPaymentId' => $result['newPayment']?->getId()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/export', methods: ['GET'])]
    public function export(Request $request, PaymentService $paymentService): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $filters = [
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
            'status' => $request->query->get('status'),
            'userId' => $user->isAdmin() ? $request->query->get('userId') : $user->getId()
        ];

        $format = $request->query->get('format', 'csv');

        try {
            $exportData = $paymentService->exportPayments($filters, $format);

            $response = new Response($exportData['content']);
            $response->headers->set('Content-Type', $exportData['contentType']);
            $response->headers->set('Content-Disposition', 
                sprintf('attachment; filename="%s"', $exportData['filename']));

            return $response;
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/stats', methods: ['GET'])]
    public function stats(Request $request, PaymentService $paymentService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $period = $request->query->get('period', 'month');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        $filters = [];
        if ($startDate) $filters['startDate'] = $startDate;
        if ($endDate) $filters['endDate'] = $endDate;
        
        // Если не админ, показываем только свои платежи
        if (!$user->isAdmin()) {
            $filters['userId'] = $user->getId();
        } else {
            if ($request->query->has('userId')) {
                $filters['userId'] = $request->query->getInt('userId');
            }
        }

        try {
            $stats = $paymentService->getPaymentStatistics($filters, $period);

            return $this->json($stats);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
