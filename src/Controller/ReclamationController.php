<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Client;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ReclamationController extends AbstractController
{
    #[Route('/reclamations', name: 'liste_reclamations')]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();

        return $this->render('reclamations/liste.html.twig', [
            'reclamations' => $reclamations
        ]);
    }





    #[Route('/reclamations/ajout', name: 'ajout_reclamation')]
    public function add(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $validator->validate($reclamation);

            if (count($errors) > 0) {
                return $this->render('reclamations/ajout.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $errors
                ]);
            }

            $reclamation->setDateCreation(new \DateTime());
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
    public function edit(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
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
}
