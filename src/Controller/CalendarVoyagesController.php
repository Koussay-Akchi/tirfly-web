<?php

namespace App\Controller;

use CalendarBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\VoyageRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarVoyagesController extends AbstractController
{
    #[Route('/fc-load-events', name: 'fc_load_events', methods: ['POST'])]
    public function loadEvents(VoyageRepository $voyageRepository, UrlGeneratorInterface $router): JsonResponse
    {
        $voyages = $voyageRepository->findAll();

        $events = [];

        foreach ($voyages as $voyage) {
            $events[] = [
                'id' => $voyage->getId(),
                'title' => $voyage->getNom(),
                'start' => $voyage->getDateDepart()->format('Y-m-d'),
                'end' => $voyage->getDateArrive()->format('Y-m-d'),

            ];
        }

        return new JsonResponse($events);
    }
}
