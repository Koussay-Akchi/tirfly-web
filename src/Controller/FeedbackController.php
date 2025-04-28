<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\Voyage;
use App\Entity\Client;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Knp\Component\Pager\PaginatorInterface;

class FeedbackController extends AbstractController
{ 
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    // Route pour afficher tous les feedbacks
    #[Route('/feedbacks', name: 'liste_feedbacks')]
    public function index(FeedbackRepository $feedbackRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $feedbacksQuery = $feedbackRepository->createQueryBuilder('f')
            ->orderBy('f.dateFeedback', 'DESC')
            ->getQuery();
    
        $pagination = $paginator->paginate(
            $feedbacksQuery,
            $request->query->getInt('page', 1), // page actuelle
            5 // nombre de feedbacks par page
        );

        if (!$this->isGranted("ROLE_CLIENT")) {
            $feedbacks = $feedbackRepository->findAll();
            return $this->render('feedbacks/liste.feedbackadmin.html.twig', ['feedbacks' => $feedbacks]);
        } else {
            return $this->render('feedbacks/liste.html.twig', [
                'feedbacks' => $pagination,
            ]);
        }
    }

    // Route pour ajouter un feedback à un voyage spécifique
    #[Route('/voyage/{id}/feedback', name: 'ajouter_feedbackk', methods: ['GET', 'POST'])]
    public function ajout(int $id, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, FeedbackRepository $feedbackRepository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);
        $client = $this->security->getUser();

        if (!$voyage) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => 'Voyage introuvable.'], 404);
            }
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        if (!$client) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => 'Vous devez être connecté pour laisser un feedback.'], 401);
            }
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            // Handle POST request (from AJAX or direct form submission)
            $note = $request->request->get('note');
            $comment = $request->request->get('comment');
            $submittedToken = $request->request->get('_token');

            // CSRF validation
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('feedback' . $id, $submittedToken))) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['message' => 'Token CSRF invalide.'], 400);
                }
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('details_voyage', ['id' => $id]);
            }

            // Validate input
            if (!is_numeric($note) || $note < 1 || $note > 5) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['message' => 'La note doit être un nombre entre 1 et 5.'], 400);
                }
                $this->addFlash('error', 'La note doit être un nombre entre 1 et 5.');
                return $this->redirectToRoute('details_voyage', ['id' => $id]);
            }

            if (empty(trim($comment))) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['message' => 'Le commentaire ne peut pas être vide.'], 400);
                }
                $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
                return $this->redirectToRoute('details_voyage', ['id' => $id]);
            }

            // Create feedback
            $feedback = new Feedback();
            $feedback->setVoyage($voyage);
            $feedback->setClient($client);
            $feedback->setNote((int) $note);
            $feedback->setContenu($comment);
            $feedback->setDateFeedback(new \DateTime());

            // Validate entity
            $errors = $validator->validate($feedback);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['message' => implode(' ', $errorMessages)], 400);
                }
                $this->addFlash('error', implode(' ', $errorMessages));
                return $this->redirectToRoute('details_voyage', ['id' => $id]);
            }

            // Persist feedback
            $entityManager->persist($feedback);
            $entityManager->flush();

            $nouveauVoyage = $voyage->updateNote();
            $entityManager->persist($nouveauVoyage);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => 'Feedback ajouté avec succès.'], 200);
            }

            $this->addFlash('success', 'Feedback ajouté avec succès.');
            return $this->redirectToRoute('details_voyage', ['id' => $id]);
        }

        // Render feedback form page for GET requests
        return $this->render('feedbacks/ajout.html.twig', [
            'voyage' => $voyage,
        ]);
    }
    #[Route('/feedback/edit/{id}', name: 'edit_feedback')]
    public function edit(?Feedback $feedback, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$feedback) {
            $this->addFlash('error', 'Feedback introuvable.');
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(FeedbackType::class, $feedback, [
            'hide_voyage_client' => true, // Hide client and voyage fields
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Optionally, set dateFeedback from the hidden field if needed
            if ($form->has('dateFeedback')) {
                $feedback->setDateFeedback(new \DateTime($form->get('dateFeedback')->getData()));
            }
            $entityManager->flush();
            
            $voyage = $feedback->getVoyage();
            $nouveauVoyage = $voyage->updateNote();
            $entityManager->persist($nouveauVoyage);
            $entityManager->flush();

            $this->addFlash('success', 'Feedback modifié avec succès.');
            return $this->redirectToRoute('details_voyage', ['id' => $feedback->getVoyage()->getId()]);
        }

        return $this->render('feedbacks/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/feedback/delete/{id}', name: 'delete_feedback', methods: ['POST'])]
    public function delete(Request $request, ?Feedback $feedback, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$feedback) {
            $this->addFlash('error', 'Feedback introuvable.');
            return $this->redirectToRoute('home');
        }

        $submittedToken = $request->request->get('_token');

        if ($csrfTokenManager->isTokenValid(new CsrfToken('delete' . $feedback->getId(), $submittedToken))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide, suppression annulée.');
        }

        return $this->redirectToRoute('details_voyage', ['id' => $feedback->getVoyage()->getId()]);
    }
    // Route pour voir les feedbacks d'un voyage spécifique
    #[Route('/voyage/{id}/feedbacks', name: 'voir_feedbacks_voyage')]
    public function voirFeedbacksVoyage(int $id, FeedbackRepository $feedbackRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        // Récupérer les feedbacks associés au voyage
        $feedbacksQuery = $feedbackRepository->createQueryBuilder('f')
            ->where('f.voyage = :voyage')
            ->setParameter('voyage', $voyage)
            ->orderBy('f.dateFeedback', 'DESC')
            ->getQuery();

        // Pagination
        $pagination = $paginator->paginate(
            $feedbacksQuery,
            $request->query->getInt('page', 1),
            5 // Nombre de feedbacks par page
        );

        return $this->render('feedbacks/voir_feedbacks_voyage.html.twig', [
            'voyage' => $voyage,
            'feedbacks' => $pagination,
        ]);
    }
    #[Route('/admin/feedbacks', name: 'admin_liste_feedbacks')]
public function adminListeVoyages(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
{
    // Vérifier que l'utilisateur est un administrateur
    $this->denyAccessUnlessGranted('ROLE_USER');

    // Récupérer tous les voyages
    $voyagesQuery = $entityManager->getRepository(Voyage::class)->createQueryBuilder('v')
        ->orderBy('v.nom', 'ASC')
        ->getQuery();

    // Pagination
    $pagination = $paginator->paginate(
        $voyagesQuery,
        $request->query->getInt('page', 1),
        5 // Nombre de voyages par page
    );

    return $this->render('feedbacks/admin_liste_feedbacks.html.twig', [
        'voyages' => $pagination,
    ]);
}
}