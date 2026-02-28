<?php

namespace App\Controller\Api;

use App\Service\EventAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class EventAnalyticsController extends AbstractController
{
    #[Route('/api/evenement/analytics', name: 'api_event_analytics', methods: ['GET'])]
    public function analytics(EventAnalyticsService $analyticsService): JsonResponse
    {
        return $this->json($analyticsService->getAnalytics());
    }
}