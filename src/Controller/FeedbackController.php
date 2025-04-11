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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FeedbackController extends AbstractController
{
    // Route pour afficher tous les feedbacks
    #[Route('/feedbacks', name: 'liste_feedbacks')]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        $feedbacks = $feedbackRepository->findAll();

        return $this->render('feedbacks/liste.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }

    // Route pour ajouter un feedback à un voyage spécifique
    #[Route('/voyage/{id}/feedback', name: 'ajouter_feedback')]
    public function ajout(int $id, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);
        $client = $entityManager->getRepository(Client::class)->find(2);
    
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }
    
        $feedback = new Feedback();
        $feedback->setVoyage($voyage);
        $feedback->setClient($client);
    
        $form = $this->createForm(FeedbackType::class, $feedback, [
            'hide_voyage_client' => true, // Option personnalisée ajoutée dans FeedbackType
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Validate entity manually
            $errors = $validator->validate($feedback);
    
            if (count($errors) > 0) {
                // Log validation errors in the Symfony profiler or console
                foreach ($errors as $error) {
                    // This logs the error in the Symfony profiler or console log
                    $this->get('logger')->error($error->getMessage());
                }
    
                // Optionally add a flash message for the user
                $this->addFlash('error', 'Veuillez corriger les erreurs de validation.');
                
                // Return to the same page if there are errors
                return $this->render('feedbacks/ajout.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
    
            // If no validation errors, persist feedback
            $entityManager->persist($feedback);
            $entityManager->flush();
    
            $this->addFlash('success', 'Feedback ajouté avec succès.');
            return $this->redirectToRoute('liste_feedbacks');
        }
    
        return $this->render('feedbacks/ajout.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    // Route pour modifier un feedback
    #[Route('/feedback/edit/{id}', name: 'edit_feedback')]
    public function edit(Feedback $feedback, Request $request, EntityManagerInterface $entityManager): Response
    {


        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Feedback modifié avec succès.');
            return $this->redirectToRoute('liste_feedbacks');
        }

        return $this->render('feedbacks/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Route pour supprimer un feedback (avec sécurité CSRF)
    #[Route('/feedback/delete/{id}', name: 'delete_feedback', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($csrfTokenManager->isTokenValid(new CsrfToken('delete' . $feedback->getId(), $submittedToken))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide, suppression annulée.');
        }

        return $this->redirectToRoute('liste_feedbacks');
    }
}
