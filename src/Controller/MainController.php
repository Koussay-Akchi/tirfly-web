<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('main/home.html.twig');
    }

    /*
    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('main/about.html.twig');
    }  
    */

    #[Route('/destinations', name: 'liste_destinations')]
    public function listeDestinations(): Response
    {
        return $this->render('main/destination.html.twig');
    }
    
    /*
    #[Route('/voyages', name: 'liste_voyages')]
    public function listeVoyages(): Response
    {
        return $this->render('main/destination.html.twig');
    }
    */

    #[Route('/packs', name: 'liste_packs')]
    public function listePacks(): Response
    {
        return $this->render('main/destination.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('main/contact.html.twig');
    }

    #[Route('/reservation', name: 'reservation_form')]
    public function reservationForm(): Response
    {
        return $this->render('main/contact.html.twig');
    }
    
}
