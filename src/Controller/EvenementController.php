<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
class EvenementController extends AbstractController
{
    #[Route('/evenement', name: 'app_evenement_index')]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $destination = $request->query->get('destination');
    
        $evenements = $evenementRepository->findPaginatedWithFilters(
            $page, 
            6,
            $search,
            $dateDebut,
            $dateFin,
            $destination
        );
    
        // Récupérer les destinations uniques pour le filtre
        $destinations = $evenementRepository->findUniqueDestinations();
    
        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'destinations' => $destinations
        ]);
    }

    #[Route('/admin/evenement/new', name: 'app_evenement_new')]
public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    $evenement = new Evenement();
    $form = $this->createForm(EvenementType::class, $evenement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('evenement_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                return $this->redirectToRoute('app_evenement_new');
            }

            $evenement->setImage($newFilename);
        }

        $em->persist($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement ajouté avec succès.');
        return $this->redirectToRoute('admin_evenement_list');
    }

    return $this->render('evenement/new.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/admin/evenement/edit/{id}', name: 'app_evenement_edit')]
public function edit(Evenement $evenement, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    $form = $this->createForm(EvenementType::class, $evenement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('evenement_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de la nouvelle image.');
                return $this->redirectToRoute('app_evenement_edit', ['id' => $evenement->getId()]);
            }

            // Supprimer l'ancienne image si elle existe
            $oldImage = $evenement->getImage();
            if ($oldImage) {
                $oldImagePath = $this->getParameter('evenement_images_directory').'/'.$oldImage;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $evenement->setImage($newFilename);
        }

        $em->flush();

        $this->addFlash('success', 'Événement modifié avec succès.');
        return $this->redirectToRoute('admin_evenement_list');
    }

    return $this->render('evenement/edit.html.twig', [
        'form' => $form->createView(),
        'evenement' => $evenement,
    ]);
}

    #[Route('/admin/evenement/delete/{id}', name: 'app_evenement_delete')]
    public function delete(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $em->remove($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès.');
        return $this->redirectToRoute('admin_evenement_list');
    }

    #[Route('/admin/evenements', name: 'admin_evenement_list')]
    public function adminList(EvenementRepository $evenementRepository): Response
    {
        return $this->render('evenement/tableau-evenement.html.twig', [
            'evenements' => $evenementRepository->findAll(),
        ]);
    }
    #[Route('/admin/evenement/statistiques', name: 'app_evenement_stats')]
public function stats(EvenementRepository $evenementRepository): Response
{
    // Récupérer les données pour les stats
    $stats = $evenementRepository->getEventsCountByDestination();

    return $this->render('evenement/stats.html.twig', [
        'stats' => $stats,
    ]);
}

    #[Route('/api/evenements', name: 'api_evenement_list')]
    public function apiList(EvenementRepository $evenementRepository): JsonResponse
    {
        $evenements = $evenementRepository->findAll();
        $data = [];

        foreach ($evenements as $evenement) {
            $data[] = [
                'id' => $evenement->getId(),
                'title' => $evenement->getTitre(),
                'start' => $evenement->getDateDebut()->format('Y-m-d'),
                'end' => $evenement->getDateFin()->format('Y-m-d'),
                'description' => $evenement->getDescription(),
                'price' => $evenement->getPrix(),
                'destination' => $evenement->getDestination()->getVille() . ', ' . $evenement->getDestination()->getPays(),
                'image' => $evenement->getImage() ? $this->generateUrl('app_evenement_index', [], true) . 'uploads/evenements/' . $evenement->getImage() : null,
            ];
        }

        return new JsonResponse($data);
    }
}