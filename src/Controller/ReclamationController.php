<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\Client;
use App\Form\ReclamationType;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use App\Service\EmailService;
use App\Service\BadWordsFilterService;
use App\Service\SentimentAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Security;
use Knp\Component\Pager\PaginatorInterface;

class ReclamationController extends AbstractController
{
    private BadWordsFilterService $filterService;
    private $security;
    private SentimentAnalysisService $sentimentAnalysisService;

    public function __construct(
        BadWordsFilterService $filterService,
        Security $security,
        SentimentAnalysisService $sentimentAnalysisService
    ) {
        $this->filterService = $filterService;
        $this->security = $security;
        $this->sentimentAnalysisService = $sentimentAnalysisService;
    }

    #[Route('/reclamations', name: 'liste_reclamations')]
    public function index(Request $request, ReclamationRepository $reclamationRepository, PaginatorInterface $paginator): Response
    {
        $etat = $request->query->get('etat');
        $queryBuilder = $reclamationRepository->createQueryBuilder('r')->orderBy('r.dateCreation', 'DESC');
    
        if ($etat) {
            $queryBuilder->where('r.etat = :etat')->setParameter('etat', $etat);
        }
    
        $query = $queryBuilder->getQuery();
        $pagination = $paginator->paginate($query, $request->query->getInt('page', 1), 5);
    
        $reclamationsAll = $reclamationRepository->findAll();
        $stats = ['total' => count($reclamationsAll), 'etat' => []];
    
        // Calculate reclamation status stats
        foreach ($reclamationsAll as $reclamation) {
            $etatValue = $reclamation->getEtat();
            $stats['etat'][$etatValue] = ($stats['etat'][$etatValue] ?? 0) + 1;
        }
    
        // Calculate response stats
        $responseStats = ['Avec réponse' => 0, 'Sans réponse' => 0];
        foreach ($reclamationsAll as $reclamation) {
            if ($reclamation->getReponses()->count() > 0) {
                $responseStats['Avec réponse']++;
            } else {
                $responseStats['Sans réponse']++;
            }
        }
    
        // Define possible etats
        $etats = [
            'Qualité de service',
            'Retard',
            'Annulation',
            'Autre'
        ];
    
        return $this->render('reclamations/liste.html.twig', [
            'reclamations' => $pagination,
            'stats' => $stats,
            'chart_labels' => array_keys($stats['etat']),
            'chart_data' => array_values($stats['etat']),
            'response_chart_labels' => array_keys($responseStats),
            'response_chart_data' => array_values($responseStats),
            'etats' => $etats,
            'etat_selectionne' => $etat
        ]);
    }

    #[Route('/reclamation/{id}/envoyer-email', name: 'envoyer_email_reclamation', methods: ['POST'])]
    public function envoyerEmail(
        Request $request,
        Reclamation $reclamation,
        EmailService $emailService
    ): JsonResponse {
        $message = $request->request->get('message');
        $client = $reclamation->getClient();

        if (!$client || !$client->getEmail()) {
            return new JsonResponse(['message' => 'Client ou email introuvable.'], 400);
        }

        if (empty($message)) {
            return new JsonResponse(['message' => 'Le message ne peut pas être vide.'], 400);
        }

        try {
            $email = $client->getEmail();
            $subject = 'Réponse à votre réclamation #' . $reclamation->getId();
            $emailService->envoyer($email, $subject, $message);
            return new JsonResponse(['message' => 'Email envoyé avec succès.'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/reclamation/add', name: 'ajout_reclamation')]
    public function add(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $reclamation = new Reclamation();
        $reclamation->setDateCreation(new \DateTime());
        $reclamation->setIsRed(false);
        $reclamation->setClient($this->security->getUser());

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $originalText = $reclamation->getDescription();
            $cleanedText = $this->filterService->analyzeText($originalText);

            if (str_contains($cleanedText, '[TAG:RED]')) {
                $reclamation->setIsRed(true);
                $cleanedText = str_replace('[TAG:RED]', '', $cleanedText);
            } else {
                $reclamation->setIsRed(false);
            }

            $reclamation->setDescription(trim($cleanedText));

            // Analyser le sentiment de la description avec Hugging Face
            $sentimentResult = $this->sentimentAnalysisService->analyzeSentiment($cleanedText);
            $reclamation->setUrgence($sentimentResult['urgence']);

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
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        return $this->render('reclamations/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamations/mon-historique', name: 'mon_historique_reclamations')]
    public function monHistorique(ReclamationRepository $reclamationRepository): Response
    {
        $user = $this->security->getUser();
        $client = $user instanceof Client ? $user : null;
        $reclamations = $reclamationRepository->findBy(['client' => $client]);

        return $this->render('reclamations/historique.html.twig', [
            'reclamations' => $reclamations,
            'client' => $user
        ]);
    }

    #[Route('/reclamations/client/edit/{id}', name: 'client_edit_reclamation')]
    public function clientEdit(
        Reclamation $reclamation,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        // Check if the current user is the client who owns the reclamation
        if ($reclamation->getClient() !== $security->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à modifier cette réclamation.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        // Check if the reclamation has any responses
        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier une réclamation qui a déjà une réponse.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $originalText = $reclamation->getDescription();
            $cleanedText = $this->filterService->analyzeText($originalText);

            if (str_contains($cleanedText, '[TAG:RED]')) {
                $reclamation->setIsRed(true);
                $cleanedText = str_replace('[TAG:RED]', '', $cleanedText);
            } else {
                $reclamation->setIsRed(false);
            }

            $reclamation->setDescription(trim($cleanedText));

            // Analyser le sentiment de la description mise à jour
            $sentimentResult = $this->sentimentAnalysisService->analyzeSentiment($cleanedText);
            $reclamation->setUrgence($sentimentResult['urgence']);

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

            $entityManager->flush();

            $this->addFlash('success', 'Réclamation modifiée avec succès.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        return $this->render('reclamations/edit.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation
        ]);
    }

    #[Route('/reclamations/client/delete/{id}', name: 'client_delete_reclamation')]
    public function clientDelete(
        Reclamation $reclamation,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        // Check if the current user is the client who owns the reclamation
        if ($reclamation->getClient() !== $security->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à supprimer cette réclamation.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        // Check if the reclamation has any responses
        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer une réclamation qui a déjà une réponse.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        $entityManager->remove($reclamation);
        $entityManager->flush();

        $this->addFlash('success', 'Réclamation supprimée avec succès.');
        return $this->redirectToRoute('mon_historique_reclamations');
    }

    #[Route('/reclamations/edit/{id}', name: 'edit_reclamation')]
    public function edit(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Analyser le sentiment de la description mise à jour
            $cleanedText = $this->filterService->analyzeText($reclamation->getDescription());
            $sentimentResult = $this->sentimentAnalysisService->analyzeSentiment($cleanedText);
            $reclamation->setUrgence($sentimentResult['urgence']);

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
    public function details(Reclamation $reclamation, Security $security): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->render('reclamations/detailsadmin.html.twig', [
                'reclamation' => $reclamation,
            ]);
        } else if ($this->isGranted('ROLE_CLIENT')) {
            return $this->render('reclamations/details.html.twig', [
                'reclamation' => $reclamation,
            ]);
        }
    }

    #[Route('/reclamations/statistiques', name: 'statistiques_reclamations')]
    public function statistiques(ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
        $stats = [];

        foreach ($reclamations as $reclamation) {
            $etat = $reclamation->getEtat();
            $stats[$etat] = ($stats[$etat] ?? 0) + 1;
        }

        return $this->render('reclamations/statistiques.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/reclamation/{id}/repondre', name: 'repondre_reclamation')]
    public function repondre(
        Request $request,
        Reclamation $reclamation,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('danger', 'Une réponse existe déjà pour cette réclamation.');
            return $this->redirectToRoute('liste_reclamations');
        }

        $reponse = new Reponse();
        $reponse->setDateReponse(new \DateTime());
        $reponse->setAuteur($security->getUser());
        $reponse->setReclamation($reclamation);

        $form = $this->createForm(ReponseType::class, $reponse, [
            'limited_fields' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->addReponse($reponse);
            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Réponse envoyée avec succès');
            return $this->redirectToRoute('details_reclamation', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamations/repondre.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation
        ]);
    }
}