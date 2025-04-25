<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use App\Service\EmailService;
#[Route('/reclamation/reponse')]
final class ReponseController extends AbstractController
{
    #[Route(name: 'app_reponse_index', methods: ['GET'])]
    public function index(ReponseRepository $reponseRepository): Response
    {
        return $this->render('reponse/index.html.twig', [
            'reponses' => $reponseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reponse);
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }
    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Reponse $reponse,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $form = $this->createForm(ReponseType::class, $reponse, [
            'limited_fields' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réponse modifiée avec succès !');

            // Send email to client
            $reclamation = $reponse->getReclamation();
            $client = $reclamation->getClient();
            if ($client && $client->getEmail()) {
                try {
                    $subject = 'Mise à jour de la réponse à votre réclamation #' . $reclamation->getId();
                    $message = "Bonjour {$client->getPrenom()} {$client->getNom()},\n\n" .
                               "La réponse à votre réclamation intitulée \"{$reclamation->getTitre()}\" a été mise à jour.\n" .
                               "Voici la nouvelle réponse :\n\n" .
                               "{$reponse->getContenu()}\n\n" .
                               "Vous pouvez consulter les détails dans votre historique de réclamations.\n" .
                               "Cordialement,\nL'équipe de support";
                    $emailService->envoyer($client->getEmail(), $subject, $message);
                    $this->addFlash('success', 'Réponse modifiée et email envoyé au client.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Réponse modifiée, mais erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('warning', 'Réponse modifiée, mais aucun email client trouvé.');
            }

            // Redirect to the reclamation details page
            return $this->redirectToRoute('details_reclamation', ['id' => $reclamation->getId()]);
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{id}', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/client/list', name: 'app_reponse_list_client', methods: ['GET'])]
    public function listClient(Security $security, ReponseRepository $reponseRepository): Response
    {
        $client = $security->getUser();
        if (!$client) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos réponses.');
        }

        $reponses = $reponseRepository->findByClient($client);

        return $this->render('reponse/list_client.html.twig', [
            'reponses' => $reponses,
        ]);
    }

    #[Route('/client/details/{id}', name: 'app_reponse_details_client', methods: ['GET'])]
    public function detailsClient(Reponse $reponse, Security $security): Response
    {
        $client = $security->getUser();
        if (!$client || $reponse->getReclamation()->getClient() !== $client) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette réponse.');
        }

        return $this->render('reponse/details_reponse.html.twig', [
            'reponse' => $reponse,
            'reclamation' => $reponse->getReclamation(),
        ]);
    }
}