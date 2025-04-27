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



class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher, // Corrected type hint
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

        $response = new JsonResponse(['message' => 'Login successful']);
        
        // Set the JWT in an HTTP-only cookie
        $cookie = new Cookie(
            'BEARER',           // Cookie name
            $token,             // Cookie value (JWT)
            time() + 3600*24,     // Expiration time (1 day)
            '/',                // Path
            null,               // Domain (null for current domain)
            false,              // Secure (false for HTTP; set to true for HTTPS)
            true                // HTTP-only (prevents JavaScript access)
        );
        $response->headers->setCookie($cookie);

        return $response;
    }
    // Sign Up
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
        $role = $data['role'] ?? 'CLIENT'; // Default to CLIENT if no role is provided
        $dateCreation = new \DateTime();
        $dateCreation->setTimezone(new \DateTimeZone('UTC'));

        // Validate required fields for all users
        if (!$email || !$password || !$nom || !$prenom) {
            return new JsonResponse(['error' => 'Email, password, nom, and prenom are required'], 400);
        }
     
        // Additional validation for CLIENT role
        if ($role === 'CLIENT' && (!$adresse || !$age || !$sexe || !$phoneNumber)) {
            return new JsonResponse(['error' => 'Adresse, age, sexe, and phoneNumber are required for CLIENT role'], 400);
        }

        // Check if email already exists
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already exists'], 400);
        }
        // check if the password if valid
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

        // Create the appropriate entity based on role
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

        // Set common User properties
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
    return new JsonResponse(['error' => $message], 400);
        }

    $em->persist($user);

        // Create Niveau for CLIENT role only
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
        // Invalidate the JWT by removing it from the cookie
        $response = new JsonResponse(['message' => 'Logout successful']);
        $cookie = new Cookie('BEARER', '', time() - 3600, '/');
        $response->headers->setCookie($cookie);

        return $response;
    }
    //Forget Pass
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

        // Generate a reset token and store it
        $resetToken = bin2hex(random_bytes(32));
        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt(new \DateTime('+1 day')); // Token valid for 1 day
        
        $entityManager->flush();

        // Send reset email
        try {
            $resetUrl = $this->generateUrl(
                'api_reset_password',
                ['token' => $resetToken],
                \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL // Generate absolute URL
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
            dd("Token not found: " . $token); // Debugging line
        }
    
        $now = new \DateTime();
        $expiresAt = $user->getResetTokenExpiresAt();
    
        if ($expiresAt < $now) {
            dd("Token expired. Now: $now | Expires At: $expiresAt"); // Debugging line
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

        // Check if the new password is different from the old one
        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            return new JsonResponse(['error' => 'New password must be different from the old one'], 400);
        }
        // Check if the new password is valid
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


        // Hash and set the new password
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setMotDePasse($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Password reset successfully'], 200);
    }
}