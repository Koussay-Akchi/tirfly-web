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
        $urgence = $request->query->get('urgence');
        $nonRepondu = $request->query->get('nonRepondu');

        // Convertir nonRepondu en booléen ou null
        $nonReponduFilter = null;
        if ($nonRepondu === '1') {
            $nonReponduFilter = true; // Réclamations non répondues
        } elseif ($nonRepondu === '0') {
            $nonReponduFilter = false; // Réclamations répondues
        }

        // Construire la requête avec tri par date de création (plus récent en premier)
        $queryBuilder = $reclamationRepository->createQueryBuilder('r')
            ->leftJoin('r.reponses', 'rep')
            ->orderBy('r.dateCreation', 'DESC'); // Sort by dateCreation, newest first

        if ($etat) {
            $queryBuilder->andWhere('r.etat = :etat')
                         ->setParameter('etat', $etat);
        }

        if ($urgence) {
            $queryBuilder->andWhere('r.urgence = :urgence')
                         ->setParameter('urgence', $urgence);
        }

        if ($nonReponduFilter !== null) {
            if ($nonReponduFilter) {
                $queryBuilder->andWhere('rep.id IS NULL');
            } else {
                $queryBuilder->andWhere('rep.id IS NOT NULL');
            }
        }

        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6,
            ['distinct' => false] // Assurer la compatibilité avec les requêtes complexes
        );

        $reclamationsAll = $reclamationRepository->findAll();
        $stats = ['total' => count($reclamationsAll), 'etat' => []];

        foreach ($reclamationsAll as $reclamation) {
            $etatValue = $reclamation->getEtat();
            $stats['etat'][$etatValue] = ($stats['etat'][$etatValue] ?? 0) + 1;
        }

        $responseStats = ['Avec réponse' => 0, 'Sans réponse' => 0];
        foreach ($reclamationsAll as $reclamation) {
            if ($reclamation->getReponses()->count() > 0) {
                $responseStats['Avec réponse']++;
            } else {
                $responseStats['Sans réponse']++;
            }
        }

        $today = new \DateTime('today');
        $todayReclamations = $reclamationRepository->createQueryBuilder('r')
            ->where('r.dateCreation >= :start')
            ->andWhere('r.dateCreation < :end')
            ->setParameter('start', $today)
            ->setParameter('end', (clone $today)->modify('+1 day'))
            ->getQuery()
            ->getResult();
        $dailyReclamationCount = count($todayReclamations);

        $etats = [
            'Qualité de service',
            'Retard',
            'Annulation',
            'Autre'
        ];

        $urgences = [
            'Normal',
            'Urgent',
            'Inacceptable'
        ];

        return $this->render('reclamations/liste.html.twig', [
            'reclamations' => $pagination,
            'stats' => $stats,
            'chart_labels' => array_keys($stats['etat']),
            'chart_data' => array_values($stats['etat']),
            'response_chart_labels' => array_keys($responseStats),
            'response_chart_data' => array_values($responseStats),
            'etats' => $etats,
            'urgences' => $urgences,
            'etat_selectionne' => $etat,
            'urgence_selectionnee' => $urgence,
            'non_repondu_selectionne' => $nonRepondu,
            'daily_reclamation_count' => $dailyReclamationCount
        ]);
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
            $description = $reclamation->getDescription();

            // Vérifier si la description contient des mots inappropriés
            if ($this->filterService->containsInappropriateWords($description)) {
                $this->addFlash('danger', 'Votre réclamation contient des mots inappropriés. Veuillez utiliser un langage respectueux.');
                return $this->render('reclamations/ajout.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Si le texte est approprié, procéder à l'enregistrement
            $reclamation->setDescription(trim($description));

            // Analyser le sentiment de la description
            $sentimentResult = $this->sentimentAnalysisService->analyzeSentiment($description);
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
                    return $this->render('reclamations/ajout.html.twig', [
                        'form' => $form->createView(),
                    ]);
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
        Security $security,
        SluggerInterface $slugger
    ): Response {
        if ($reclamation->getClient() !== $security->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à modifier cette réclamation.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier une réclamation qui a déjà une réponse.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $description = $reclamation->getDescription();

            // Vérifier si la description contient des mots inappropriés
            if ($this->filterService->containsInappropriateWords($description)) {
                $this->addFlash('danger', 'Votre réclamation contient des mots inappropriés. Veuillez utiliser un langage respectueux.');
                return $this->render('reclamations/edit.html.twig', [
                    'form' => $form->createView(),
                    'reclamation' => $reclamation
                ]);
            }

            $reclamation->setDescription(trim($description));

            $sentimentResult = $this->sentimentAnalysisService->analyzeSentiment($description);
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
                    return $this->render('reclamations/edit.html.twig', [
                        'form' => $form->createView(),
                        'reclamation' => $reclamation
                    ]);
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
        if ($reclamation->getClient() !== $security->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à supprimer cette réclamation.');
            return $this->redirectToRoute('mon_historique_reclamations');
        }

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
            $description = $reclamation->getDescription();

            // Vérifier si la description contient des mots inappropriés
            if ($this->filterService->containsInappropriateWords($description)) {
                $this->addFlash('danger', 'Votre réclamation contient des mots inappropriés. Veuillez utiliser un langage respectueux.');
                return $this->render('reclamations/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $reclamation->setDescription(trim($description));
            $sentimentResult = $this->sentimentAnalysisService->analyzeSentiment($description);
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
        Security $security,
        EmailService $emailService
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

            $client = $reclamation->getClient();
            if ($client && $client->getEmail()) {
                try {
                    $subject = 'Réponse à votre réclamation #' . $reclamation->getId();
                    $message = "Bonjour {$client->getPrenom()} {$client->getNom()},\n\n" .
                               "Nous avons répondu à votre réclamation intitulée \"{$reclamation->getTitre()}\".\n" .
                               "Voici la réponse :\n\n" .
                               "{$reponse->getContenu()}\n\n" .
                               "Vous pouvez consulter les détails dans votre historique de réclamations.\n" .
                               "Cordialement,\nL'équipe de support";
                    $emailService->envoyer($client->getEmail(), $subject, $message);
                    $this->addFlash('success', 'Réponse envoyée avec succès et email envoyé au client.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Réponse envoyée, mais erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('warning', 'Réponse envoyée, mais aucun email client trouvé.');
            }

            return $this->redirectToRoute('details_reclamation', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamations/repondre.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation
        ]);
    }
}