<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\VoyageType;

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

    #[Route('/voyages/ajout', name: 'ajout_voyage')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            if ($voyage->getDateArrive() < $voyage->getDateDepart()) {
                $this->addFlash('error', "La date d'arrivée doit etre après la date de départ.");
                return $this->redirectToRoute('ajout_voyage');
            }

            /*
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('voyage_images_directory'),
                        $newFilename
                    );
                    $voyage->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                }
            }
            */
            $voyage->setNote(0);
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage ajouté avec succès.');
            return $this->redirectToRoute('liste_voyages');
        }
        return $this->render('voyages/ajout-voyage.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
