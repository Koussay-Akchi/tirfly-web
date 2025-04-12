<?php

namespace App\Controller;

use App\Entity\Niveau;
use App\Form\NiveauType;
use App\Repository\NiveauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
#[Route('/niveau')]
final class NiveauController extends AbstractController{
    #[Route(name: 'app_niveau_index', methods: ['GET'])]
    public function index(NiveauRepository $niveauRepository): Response
    {
        return $this->render('niveau/index.html.twig', [
            'niveaux' => $niveauRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_niveau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $niveau = new Niveau();
        $form = $this->createForm(NiveauType::class, $niveau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($niveau);
            $entityManager->flush();

            return $this->redirectToRoute('app_niveau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau/new.html.twig', [
            'niveau' => $niveau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_niveau_show', methods: ['GET'])]
    public function show(Niveau $niveau): Response
    {
        return $this->render('niveau/show.html.twig', [
            'niveau' => $niveau,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_niveau_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Niveau $niveau, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $form = $this->createForm(NiveauType::class, $niveau);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            $logger->info('Form submitted', [
                'data' => $form->getData(),
                'is_valid' => $form->isValid(),
                'errors' => (string) $form->getErrors(true),
            ]);
    
            if ($form->isValid()) {
                $entityManager->persist($niveau); // Ensure entity is managed
                $entityManager->flush();
                return $this->redirectToRoute('app_niveau_index', [], Response::HTTP_SEE_OTHER);
            }
        }
    
        return $this->render('niveau/edit.html.twig', [
            'niveau' => $niveau,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_niveau_delete', methods: ['POST'])]
    public function delete(Request $request, Niveau $niveau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$niveau->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($niveau);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_niveau_index', [], Response::HTTP_SEE_OTHER);
    }
    public function AddXPToNiveau(Niveau $niveau, int $xpToAdd): void
    {
        $niveau->setNiveauXP($niveau->getNiveauXP() + $xpToAdd);
        while ($niveau->getNiveauXP() >= $niveau->getMaxNiveauXP()) {
            $niveau->setNiveau($niveau->getNiveau() + 1);
            $niveau->setNiveauXP($niveau->getNiveauXP() - $niveau->getMaxNiveauXP());
            $niveau->setMaxNiveauXP($niveau->getMaxNiveauXP() + 100);
        }
        $this->getDoctrine()->getManager()->persist($niveau);
        $this->getDoctrine()->getManager()->flush();
    }
    public function AddLevelToNiveau(Niveau $niveau, int $levelToAdd): void
    {
        $niveau->setNiveau($niveau->getNiveau() + $levelToAdd);
        $niveau->setNiveauXP(0);
        $niveau->setMaxNiveauXP($niveau->getMaxNiveauXP() + 100 * $levelToAdd);
        $this->getDoctrine()->getManager()->flush();
    }

}
