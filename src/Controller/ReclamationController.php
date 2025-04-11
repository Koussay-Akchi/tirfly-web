<?php
namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\BadWordsFilterService;

class ReclamationController extends AbstractController
{
    private BadWordsFilterService $filterService;

    public function __construct(BadWordsFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    #[Route('/reclamations', name: 'liste_reclamations')]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        // Récupérer toutes les réclamations
        $reclamations = $reclamationRepository->findAll();
    
        // Calcul des statistiques
        $stats = [
            'total' => count($reclamations),
            'etat' => []
        ];
    
        // Comptabiliser les réclamations par état
        foreach ($reclamations as $reclamation) {
            $etat = $reclamation->getEtat();
            if (!isset($stats['etat'][$etat])) {
                $stats['etat'][$etat] = 0;
            }
            $stats['etat'][$etat]++;
        }
    
        // Préparer les données pour Chart.js
        $labels = array_keys($stats['etat']);
        $data = array_values($stats['etat']);
    
        return $this->render('reclamations/liste.html.twig', [
            'reclamations' => $reclamations,
            'stats' => $stats,
            'chart_labels' => $labels,
            'chart_data' => $data,
        ]);
    }
    

    #[Route('/reclamation/add', name: 'ajout_reclamation')]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Utiliser le service injecté via la propriété pour analyser le texte
            $originalText = $reclamation->getDescription();
            $cleanedText = $this->filterService->analyzeText($originalText);

            // Si un tag est présent, on marque la réclamation comme « rouge »
            if (str_contains($cleanedText, '[TAG:RED]')) {
                $reclamation->setIsRed(true);
                $cleanedText = str_replace('[TAG:RED]', '', $cleanedText);
            } else {
                $reclamation->setIsRed(false);
            }
            $reclamation->setDescription(trim($cleanedText));

            // Gestion de l'upload de vidéo (si applicable)
            $videoFile = $form->get('videoPath')->getData();
            if ($videoFile) {
                $originalFilename = pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $videoFile->guessExtension();

                try {
                    $videoFile->move(
                        $this->getParameter('video_directory'),
                        $newFilename
                    );
                    $reclamation->setVideoPath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de la vidéo.');
                }
            }

            $entityManager->persist($reclamation);
            $entityManager->flush();

            $this->addFlash('success', 'Réclamation ajoutée avec succès.');
            return $this->redirectToRoute('liste_reclamations');
        }

        return $this->render('reclamations/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamations/edit/{id}', name: 'edit_reclamation')]
    public function edit(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Réclamation modifiée avec succès.');
            return $this->redirectToRoute('liste_reclamations');
        }

        return $this->render('reclamations/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamations/delete/{id}', name: 'delete_reclamation')]
    public function delete(Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($reclamation);
        $entityManager->flush();

        $this->addFlash('success', 'Réclamation supprimée avec succès.');
        return $this->redirectToRoute('liste_reclamations');
    }

    #[Route('/reclamations/{id}', name: 'details_reclamation')]
    public function details(Reclamation $reclamation): Response
    {
        return $this->render('reclamations/details.html.twig', [
            'reclamation' => $reclamation
        ]);
    }
    #[Route('/reclamations/statistiques', name: 'statistiques_reclamations')]
public function statistiques(ReclamationRepository $reclamationRepository): Response
{
    $reclamations = $reclamationRepository->findAll();

    $stats = [];
    foreach ($reclamations as $reclamation) {
        $etat = $reclamation->getEtat();
        if (!isset($stats[$etat])) {
            $stats[$etat] = 0;
        }
        $stats[$etat]++;
    }

    return $this->render('reclamations/statistiques.html.twig', [
        'stats' => $stats
    ]);
}
}
