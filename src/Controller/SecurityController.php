<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Niveau;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\EmailService;
use Symfony\Component\HttpFoundation\Response;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\FacebookUser;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtManager->create($user);
        $role = $user->getRoles()[0];

        $response = new JsonResponse(['message' => 'Login successful', 'role' => $role]);
        
        $cookie = new Cookie(
            'BEARER',
            $token,
            time() + 3600*24,
            '/',
            null,
            false,
            true
        );
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/api/signup', name: 'api_signup', methods: ['POST'])]
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $nom = $data['nom'] ?? null;
        $prenom = $data['prenom'] ?? null;
        $adresse = $data['adresse'] ?? null;
        $age = $data['age'] ?? null;    
        $sexe = $data['sexe'] ?? null;
        $phoneNumber = $data['phoneNumber'] ?? null;
        $role = $data['role'] ?? 'CLIENT';
        $dateCreation = new \DateTime();
        $dateCreation->setTimezone(new \DateTimeZone('UTC'));

        if (!$email || !$password || !$nom || !$prenom) {
            return new JsonResponse(['error' => 'Email, password, nom, and prenom are required'], 400);
        }
     
        if ($role === 'CLIENT' && (!$adresse || !$age || !$sexe || !$phoneNumber)) {
            return new JsonResponse(['error' => 'Adresse, age, sexe, and phoneNumber are required for CLIENT role'], 400);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already exists'], 400);
        }

        if (strlen($password) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters long'], 400);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return new JsonResponse(['error' => 'Password must contain at least one uppercase letter'], 400);
        }
        if (!preg_match('/[a-z]/', $password)) {
            return new JsonResponse(['error' => 'Password must contain at least one lowercase letter'], 400);
        }
        if (!preg_match('/[0-9]/', $password)) {
            return new JsonResponse(['error' => 'Password must contain at least one number'], 400);
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return new JsonResponse(['error' => 'Password must contain at least one special character'], 400);
        }

        switch (strtoupper($role)) {
            case 'CLIENT':
                $user = new Client();
                $user->setAdresse($adresse);
                $user->setAge($age);
                $user->setSexe($sexe);
                $user->setPhoneNumber($phoneNumber);
                break;
            case 'ADMIN':
            case 'SUPPORT':
            case 'VOYAGEUR':
                $user = new User();
                break;
            default:
                return new JsonResponse(['error' => 'Invalid role provided'], 400);
        }

        $user->setEmail($email);
        $user->setMotDePasse($hasher->hashPassword($user, $password));
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setDateCreation($dateCreation);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $field = $error->getPropertyPath();
                $message = $error->getMessage();
                $errorMessages[$field] = $message;
            }
            return new JsonResponse(['error' => $errorMessages], 400);
        }

        $em->persist($user);

        if ($role === 'CLIENT') {
            $niveau = new Niveau();
            $niveau->setNiveau(1);
            $niveau->setNiveauXP(100);
            $niveau->setMaxNiveauXP(1000);
            $niveau->setClient($user);
            $user->setNiveau($niveau);
            $em->persist($niveau);
        }

        $em->flush();

        $token = $jwtManager->create($user);
        $response = new JsonResponse(['message' => 'User created successfully']);
        $cookie = new Cookie(
            'BEARER',
            $token,
            time() + 3600 * 24,
            '/',
            null,
            false,
            true
        );
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]  
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Logout successful']);
        $cookie = new Cookie('BEARER', '', time() - 3600, '/');
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/api/forgot-password', name: 'api_forget_password', methods: ['POST'])]
    public function forgetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email is required'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $resetToken = bin2hex(random_bytes(32));
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt(new \DateTime('+1 day')); 
        
        $entityManager->flush();

        try {
            $resetUrl = $this->generateUrl(
                'api_reset_password',
                ['token' => $resetToken],
                \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
            );
            $emailService->sendEmail(
                $user->getEmail(),
                'Password Reset Request',
                "Click this link to reset your password: $resetUrl"
            );
            return new JsonResponse(['message' => 'Password reset email sent'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/reset-password/{token}', name: 'api_reset_password', methods: ['GET'])]
    public function showResetPasswordForm(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
    
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid token'], 404);
        }
    
        $now = new \DateTime();
        $expiresAt = $user->getResetTokenExpiresAt();
    
        if ($expiresAt < $now) {
            return new JsonResponse(['error' => 'Token expired'], 400);
        }
    
        return $this->render('security/reset-password.html.twig', [
            'token' => $token,
        ]);
    }

    #[Route('/api/reset-password/{token}', name: 'api_reset_password_submit', methods: ['POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid token'], 404);
        }

        if ($user->getResetTokenExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Token has expired'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $newPassword = $data['password'] ?? null;

        if (!$newPassword) {
            return new JsonResponse(['error' => 'Password is required'], 400);
        }

        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            return new JsonResponse(['error' => 'New password must be different from the old one'], 400);
        }

        if (strlen($newPassword) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters long'], 400);
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            return new JsonResponse(['error' => 'Password must contain at least one uppercase letter'], 400);
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            return new JsonResponse(['error' => 'Password must contain at least one lowercase letter'], 400);
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            return new JsonResponse(['error' => 'Password must contain at least one number'], 400);
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
            return new JsonResponse(['error' => 'Password must contain at least one special character'], 400);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setMotDePasse($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Password reset successfully'], 200);
    }

    #[Route('/connect/google', name: 'connect_google_start', methods: ['GET'])]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['openid', 'email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function connectGoogleCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): Response {
        try {
            $client = $clientRegistry->getClient('google');
            $accessToken = $client->getAccessToken();
            $googleUser = $client->fetchUserFromToken($accessToken);

            $email = $googleUser->getEmail();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new Client();
                $user->setEmail($email);
                $user->setNom($googleUser->getLastName() ?? 'Unknown');
                $user->setPrenom($googleUser->getFirstName() ?? 'Unknown');
                $user->setDateCreation(new \DateTime());
                $user->setMotDePasse('');
                $user->setAdresse('');
                $user->setAge(18);
                $user->setSexe('');
                $user->setPhoneNumber('');

                $niveau = new Niveau();
                $niveau->setNiveau(1);
                $niveau->setNiveauXP(100);
                $niveau->setMaxNiveauXP(1000);
                $niveau->setClient($user);
                $user->setNiveau($niveau);

                $em->persist($niveau);
                $em->persist($user);
                $em->flush();
            }

            $token = $jwtManager->create($user);
            $response = new RedirectResponse('/');
            $cookie = new Cookie(
                'BEARER',
                $token,
                time() + 3600 * 24,
                '/',
                null,
                false,
                true
            );
            $response->headers->setCookie($cookie);

            return $response;
        } catch (\Exception $e) {
            return new RedirectResponse('/login?error=' . urlencode('Google authentication failed: ' . $e->getMessage()));
        }
    }

    #[Route('/connect/facebook', name: 'connect_facebook_start', methods: ['GET'])]
    public function connectFacebook(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('facebook')
            ->redirect(['email', 'public_profile'], []);
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check', methods: ['GET'])]
    public function connectFacebookCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): Response {
        try {
            $client = $clientRegistry->getClient('facebook');
            $accessToken = $client->getAccessToken();
            $facebookUser = $client->fetchUserFromToken($accessToken);

            $email = $facebookUser->getEmail();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new Client();
                $user->setEmail($email);
                $user->setNom($facebookUser->getLastName() ?? 'Unknown');
                $user->setPrenom($facebookUser->getFirstName() ?? 'Unknown');
                $user->setDateCreation(new \DateTime());
                $user->setMotDePasse('');
                $user->setAdresse('');
                $user->setAge(18);
                $user->setSexe('');
                $user->setPhoneNumber('');

                $niveau = new Niveau();
                $niveau->setNiveau(1);
                $niveau->setNiveauXP(100);
                $niveau->setMaxNiveauXP(1000);
                $niveau->setClient($user);
                $user->setNiveau($niveau);

                $em->persist($niveau);
                $em->persist($user);
                $em->flush();
            }

            $token = $jwtManager->create($user);
            $response = new RedirectResponse('/');
            $cookie = new Cookie(
                'BEARER',
                $token,
                time() + 3600 * 24,
                '/',
                null,
                false,
                true
            );
            $response->headers->setCookie($cookie);

            return $response;
        } catch (\Exception $e) {
            return new RedirectResponse('/login?error=' . urlencode('Facebook authentication failed: ' . $e->getMessage()));
        }
    }

    #[Route('/connect/discord', name: 'connect_discord_start', methods: ['GET'])]
    public function connectDiscord(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('discord')
            ->redirect(['identify', 'email'], []);
    }

    #[Route('/connect/discord/check', name: 'connect_discord_check', methods: ['GET'])]
    public function connectDiscordCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): Response {
        try {
            $client = $clientRegistry->getClient('discord');
            $accessToken = $client->getAccessToken();
            /** @var DiscordResourceOwner $discordUser */
            $discordUser = $client->fetchUserFromToken($accessToken);

            $email = $discordUser->getEmail();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new Client();
                $user->setEmail($email);
                $username = $discordUser->getUsername();
                $user->setNom($username ?? 'Unknown');
                $user->setPrenom('');
                $user->setDateCreation(new \DateTime());
                $user->setMotDePasse('');
                $user->setAdresse('');
                $user->setAge(18);
                $user->setSexe('');
                $user->setPhoneNumber('');

                $niveau = new Niveau();
                $niveau->setNiveau(1);
                $niveau->setNiveauXP(100);
                $niveau->setMaxNiveauXP(1000);
                $niveau->setClient($user);
                $user->setNiveau($niveau);

                $em->persist($niveau);
                $em->persist($user);
                $em->flush();
            }

            $token = $jwtManager->create($user);
            $response = new RedirectResponse('/');
            $cookie = new Cookie(
                'BEARER',
                $token,
                time() + 3600 * 24,
                '/',
                null,
                false,
                true
            );
            $response->headers->setCookie($cookie);

            return $response;
        } catch (\Exception $e) {
            return new RedirectResponse('/login?error=' . urlencode('Discord authentication failed: ' . $e->getMessage()));
        }
    }
}