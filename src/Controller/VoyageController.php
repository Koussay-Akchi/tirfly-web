<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VoyageController extends AbstractController
{
    #[Route('/voyages', name: 'liste_voyages')]
    public function index(VoyageRepository $voyageRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $dateDepart = $request->query->get('dateDepart');
        $dateArrive = $request->query->get('dateArrive');
        $country = $request->query->get('country');

        $voyages = $voyageRepository->filterVoyages($search, $dateDepart, $dateArrive, $country);
        $countries = array_unique(array_map(fn($voyage) => $voyage->getDestination()->getPays(), $voyages));


        return $this->render('voyages/liste-voyages.html.twig', [
            'voyages' => $voyages,
            'countries' => $countries
        ]);
    }
}
