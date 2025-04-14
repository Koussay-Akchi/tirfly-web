<?php

namespace App\Controller;

use App\Entity\Niveau;
use App\Form\NiveauType;
use App\Repository\NiveauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    #[Route('/leaderboard', name: 'app_niveau_leaderboard', methods: ['GET'])]
    public function leaderboard(NiveauRepository $niveauRepository): Response
    {
        $niveaux = $niveauRepository->findBy([], ['niveauXP' => 'DESC'], 10);
        return $this->render('niveau/leaderboard.html.twig', [
            'niveaux' => $niveaux,
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
    #[Route('/{id}/addXP/{xpToAdd}', name: 'app_niveau_add_xp', methods: ['POST'])]
    public function AddXPToNiveau(Request $request, Niveau $niveau = null, int $xpToAdd, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        $logger->info('AddXPToNiveau called', [
            'id' => $request->attributes->get('id'),
            'xpToAdd' => $xpToAdd,
        ]);

        if (!$niveau) {
            $logger->error('Niveau not found', ['id' => $request->attributes->get('id')]);
            return new JsonResponse(['message' => 'Level not found'], 404);
        }

        try {
            // Initialize with defaults if null
            $currentXP = $niveau->getNiveauXP() ?? 0;
            $maxXP = $niveau->getMaxNiveauXP() ?? 1000;
            $currentLevel = $niveau->getNiveau() ?? 1;

            $logger->debug('Current state', [
                'currentXP' => $currentXP,
                'maxXP' => $maxXP,
                'currentLevel' => $currentLevel,
            ]);

            $newXP = $currentXP + $xpToAdd;
            $niveau->setNiveauXP($newXP);

            while ($newXP >= $maxXP) {
                $currentLevel++;
                $newXP -= $maxXP;
                $maxXP += 100;
                $niveau->setNiveau($currentLevel);
                $niveau->setNiveauXP($newXP);
                $niveau->setMaxNiveauXP($maxXP);
            }
            
            $entityManager->persist($niveau);
            $entityManager->flush();

            $logger->info('XP added successfully', [
                'niveau' => $niveau->getNiveau(),
                'niveauXP' => $niveau->getNiveauXP(),
                'maxNiveauXP' => $niveau->getMaxNiveauXP(),
            ]);

            // Explicitly cast to integers
            $responseData = [
                'niveau' => (int)($niveau->getNiveau() ?? 1),
                'niveauXP' => (int)($niveau->getNiveauXP() ?? 0),
                'maxNiveauXP' => (int)($niveau->getMaxNiveauXP() ?? 1000),
            ];

            $logger->debug('Response data', [
                'response' => $responseData,
            ]);

            return new JsonResponse($responseData, 200);
        } catch (\Exception $e) {
            $logger->error('Error adding XP', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/add-level/{id}/{levelToAdd}', name: 'app_niveau_add_level', methods: ['POST'])]
    public function AddLevelToNiveau(Niveau $niveau, int $levelToAdd,EntityManagerInterface $entityManager): void
    {
        $niveau->setNiveau($niveau->getNiveau() + $levelToAdd);
        $niveau->setNiveauXP(0);
        $niveau->setMaxNiveauXP($niveau->getMaxNiveauXP() + 100 * $levelToAdd);
        $entityManager->persist($niveau);
        $entityManager->flush();
    }

}
