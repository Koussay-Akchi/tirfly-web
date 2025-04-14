<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Niveau;
use App\Entity\User;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\ClientPasswordType;

#[Route('/client')]
class ClientController extends AbstractController
{
    private $entityManager;
    private $security;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/', name: 'client_index', methods: ['GET'])]
    public function index(ClientRepository $clientRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $clients = $clientRepository->findAll();
        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'client_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = $this->passwordHasher->hashPassword($client, $plainPassword);
                $client->setMotDePasse($hashedPassword);
                $client->setDateCreation(new \DateTime());

                $niveau = new Niveau();
                $niveau->setNiveau(1);
                $niveau->setNiveauXP(100);
                $niveau->setMaxNiveauXP(1000);
                $niveau->setClient($client);
                $client->setNiveau($niveau);

                $this->entityManager->persist($client);
                $this->entityManager->persist($niveau);
                $this->entityManager->flush();

                $this->addFlash('success', 'Client created successfully with Niveau!');
                return $this->redirectToRoute('client_index');
            } else {
                $errors = $form->getErrors(true, true);
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'client_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Client $client): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('client_index');
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'client_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteClient(int $id, EntityManagerInterface $em): Response
    {
        $client = $em->getRepository(Client::class)->find($id);

        if (!$client) {
            throw $this->createNotFoundException('Client not found');
        }

        $connection = $em->getConnection();

        $connection->executeStatement('DELETE FROM niveaus WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM feedbacks WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM paniers WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM payments WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM reclamations WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM refund WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM reservations WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM chats WHERE client_id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM clients WHERE id = :id', ['id' => $id]);
        $connection->executeStatement('DELETE FROM users WHERE id = :id', ['id' => $id]);

        $em->remove($client);
        $em->flush();

        $this->addFlash('success', 'Client deleted successfully!');
        return $this->redirectToRoute('client_index');
    }

    #[Route('/profile', name: 'client_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException('Only clients can access this page.');
        }

        return $this->render('client/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'client_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException('Only clients can access this page.');
        }

        $form = $this->createForm(ClientType::class, $user);
        $form->remove('motDePasse');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('client_profile');
        }

        return $this->render('client/edit_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    #[Route('/profile/delete', name: 'client_profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException('Only clients can delete their profile.');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_logout');
        }

        return $this->redirectToRoute('client_profile');
    }

    #[Route('/profile/change-password', name: 'client_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        if (!$user instanceof Client) {
            throw $this->createAccessDeniedException('Only clients can change their password.');
        }

        $form = $this->createForm(ClientPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setMotDePasse($hashedPassword);
                $this->entityManager->flush();
                $this->addFlash('success', 'Password updated successfully!');
                return $this->redirectToRoute('client_profile');
            }
        }

        return $this->render('client/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}