<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PhoenixApiServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/system', name: 'admin_system_')]
final class SystemController extends AbstractController
{
    public function __construct(
        private readonly PhoenixApiServiceInterface $phoenixApiService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/api-status', name: 'api_status', methods: ['GET'])]
    public function apiStatus(): JsonResponse
    {
        try {
            $isAvailable = $this->phoenixApiService->isApiAvailable();

            return $this->json([
                'available' => $isAvailable,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to check API status', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'available' => false,
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => 'Nie można sprawdzić statusu API',
            ]);
        }
    }
}
