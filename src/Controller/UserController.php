<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Security;
class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, Security $security)
    {   $this->security = $security;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'user_list', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        return $this->json($users);
    }

    #[Route('/users/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($user);
    }

    #[Route('/users', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Check for required fields
        if (!isset($data['email'], $data['motDePasse'], $data['nom'], $data['prenom'])) {
            return $this->json(['message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setMotDePasse($data['motDePasse']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        
        // Safely set the role, making sure it's a valid role
        $role = $data['role'] ?? 'USER'; // Default to 'USER' if no role is provided
        $user->setRole($role);
        
        $user->setDateCreation(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/users/{id}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $user->setEmail($data['email'] ?? $user->getEmail());
        $user->setMotDePasse($data['motDePasse'] ?? $user->getMotDePasse());
        $user->setNom($data['nom'] ?? $user->getNom());
        $user->setPrenom($data['prenom'] ?? $user->getPrenom());
        
        // Safely update the role
        $role = $data['role'] ?? $user->getRole(); // Keep the existing role if not provided
        $user->setRole($role);

        $this->entityManager->flush();

        return $this->json(['message' => 'User updated successfully']);
    }

    #[Route('/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }

    #[Route('/users/support', name: 'get_support', methods: ['GET'])]
    public function getSupportUsers(): JsonResponse
    {
        $supportUsers = $this->userRepository->findBy(['role' => 'SUPPORT']);
        return $this->json($supportUsers);
    }

    #[Route('/users/email/{email}', name: 'get_user_by_email', methods: ['GET'])]
    public function getUserByEmail(string $email): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($user);
    }

    #[Route('/api/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'message' => 'You are authenticated!',
            'user' => $user ? $user->getUserIdentifier() : null,
        ]);
    }
#[Route('/login', name: 'login')]
public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
{
    $request->headers->remove('Authorization');
    $response = new Response();
    $response->headers->clearCookie('BEARER');
    if ($this->getUser()) {
        $this->addFlash('success', 'You are already logged in!');
        if ($this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('home');
        }
        return $this->redirectToRoute('client_index');
    }
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();
    return $this->render('security/login.html.twig', [
        'last_username' => $lastUsername,
        'error' => $error,
    ], $response);
}

    #[Route('/signup', name: 'signup')]
    public function signup(Request $request): Response
    {
        if ($this->security->getUser()) {
            if ($this->isGranted('ROLE_CLIENT')){
                return $this->redirectToRoute('home');
            }
            else {
                return $this->redirectToRoute('client_index');
            }
            return $this->redirectToRoute('home');
        }
        return $this->render('security/SignUp.html.twig');
    }
    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        $response = new RedirectResponse($this->generateUrl('login'));
        $response->headers->clearCookie('BEARER');
        return $response;
    }
    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        if ($this->security->getUser()) {
            if ($this->isGranted('ROLE_CLIENT')){
                return $this->redirectToRoute('home');
            }
            else {
                return $this->redirectToRoute('client_index');
            }
            return $this->redirectToRoute('home');
        }
        return $this->render('security/forgot_password.html.twig');
    }
}
