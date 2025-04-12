<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Niveau;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/client')]
class ClientController extends AbstractController
{
    private $entityManager;
    private $security;
    private $passwordHasher;

    // Define the constructor with all dependencies
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
                // Hash the password
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = $this->passwordHasher->hashPassword($client, $plainPassword);
                $client->setMotDePasse($hashedPassword);
                $client->setDateCreation(new \DateTime());
    
                // Create and configure the Niveau entity
                $niveau = new Niveau();
                $niveau->setNiveau(1);
                $niveau->setNiveauXP(100);
                $niveau->setMaxNiveauXP(1000);
                $niveau->setClient($client); // Link Niveau to Client
    
                // Link Client to Niveau (since it's a bidirectional one-to-one)
                $client->setNiveau($niveau);
    
                // Persist both entities
                $this->entityManager->persist($client);  // Persist Client first
                $this->entityManager->persist($niveau);  // Then persist Niveau
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

    #[Route('/{id}', name: 'client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'])]
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
    #[Route('/{id}', name: 'client_delete', methods: ['POST'])]
    public function deleteClient(int $id, EntityManagerInterface $em): Response
    {
        $client = $em->getRepository(Client::class)->find($id);
    
        if (!$client) {
            throw $this->createNotFoundException('Client not found');
        }
    
        $connection = $em->getConnection();
    
        // Step 1: Delete related entities from dependent tables
        // Delete Niveau (one-to-one relationship)
        $connection->executeStatement('DELETE FROM niveaus WHERE client_id = :id', ['id' => $id]);
    
        // Delete Feedbacks (one-to-many)
        $connection->executeStatement('DELETE FROM feedbacks WHERE client_id = :id', ['id' => $id]);
    
        // Delete Paniers (one-to-many)
        $connection->executeStatement('DELETE FROM paniers WHERE client_id = :id', ['id' => $id]);
    
        // Delete Payments (one-to-many)
        $connection->executeStatement('DELETE FROM payments WHERE client_id = :id', ['id' => $id]);
    
        // Delete Reclamations (one-to-many)
        $connection->executeStatement('DELETE FROM reclamations WHERE client_id = :id', ['id' => $id]);
    
        // Delete Refunds (one-to-many)
        $connection->executeStatement('DELETE FROM refund WHERE client_id = :id', ['id' => $id]);
    
        // Delete Reservations (one-to-many)
        $connection->executeStatement('DELETE FROM reservations WHERE client_id = :id', ['id' => $id]);
    
        // Delete from chats (many-to-many relationship with users)
        $connection->executeStatement('DELETE FROM chats WHERE client_id = :id', ['id' => $id]);
    
        // Step 2: Delete the Client row from the clients table
        $connection->executeStatement('DELETE FROM clients WHERE id = :id', ['id' => $id]);
    
        // Step 3: Remove the User entity from the users table
        $em->remove($client);
        $em->flush();
    
        $this->addFlash('success', 'Client deleted successfully!');
        return $this->redirectToRoute('client_index');
    }
}