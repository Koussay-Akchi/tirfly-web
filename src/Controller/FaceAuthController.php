<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class FaceAuthController extends AbstractController
{
    private $entityManager;
    private $jwtManager;

    public function __construct(EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager)
    {
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/api/signup/face', name: 'api_signup_face', methods: ['POST'])]
    public function saveFaceDescriptor(Request $request): JsonResponse
    {
        try {
            // Parse the JSON request body
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
            }

            $email = $data['email'] ?? null;
            $faceDescriptor = $data['faceDescriptor'] ?? null;

            // Validate inputs
            if (!$email || !is_array($faceDescriptor) || empty($faceDescriptor)) {
                return new JsonResponse(['error' => 'Missing or invalid email or faceDescriptor'], 400);
            }

            // Find the user by email
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }

            // Set the face descriptor
            $user->setFaceDescriptor($faceDescriptor);

            // Persist the user
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Face descriptor saved successfully'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to save face descriptor: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/login/face', name: 'api_login_face', methods: ['POST'])]
    public function loginWithFace(Request $request): JsonResponse
    {
        try {
            // Parse the JSON request body
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
            }

            $email = $data['email'] ?? null;
            $faceDescriptor = $data['faceDescriptor'] ?? null;

            // Log the incoming request
            error_log("Face login request received for email: " . ($email ?? 'null'));

            // Validate inputs
            if (!$email || !is_array($faceDescriptor) || empty($faceDescriptor)) {
                error_log("Validation failed: Missing or invalid email or faceDescriptor");
                return new JsonResponse(['error' => 'Missing or invalid email or faceDescriptor'], 400);
            }

            // Find the user by email
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                error_log("User not found for email: " . $email);
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            }

            // Get stored face descriptor
            $storedDescriptor = $user->getFaceDescriptor();
            if (!$storedDescriptor) {
                error_log("No face descriptor registered for user: " . $email);
                return new JsonResponse(['error' => 'No face descriptor registered for this user'], 400);
            }

            // Log descriptor lengths for debugging
            error_log("Provided descriptor length: " . count($faceDescriptor));
            error_log("Stored descriptor length: " . count($storedDescriptor));

            // Convert the provided faceDescriptor to a float array
            $providedDescriptor = array_map('floatval', $faceDescriptor);

            // Calculate Euclidean distance
            $threshold = 0.6; // Euclidean distance threshold for face matching
            $distance = $this->calculateEuclideanDistance($providedDescriptor, $storedDescriptor);

            // Log the distance
            error_log("Euclidean distance: " . $distance);

            if ($distance >= $threshold) {
                error_log("Face does not match for user: " . $email . " (Distance: $distance, Threshold: $threshold)");
                return new JsonResponse(['error' => 'Face does not match'], 401);
            }

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            // Log successful login
            error_log("Face login successful for user: " . $email);

            // Create response with cookie
            $response = new JsonResponse([
                'success' => true,
                'message' => 'Face login successful',
                'user' => $user->getEmail()
            ], 200);

            // Set the BEARER cookie with the JWT token
            $cookie = new Cookie(
                'BEARER',           // Cookie name
                $token,             // JWT token value
                time() + 3600 * 24, // Expires in 24 hours
                '/',                // Path
                null,               // Domain (null for current domain)
                false,              // Secure (false, as we're not enforcing HTTPS here; set to true in production with HTTPS)
                true                // HttpOnly (prevents client-side JavaScript access)
            );
            $response->headers->setCookie($cookie);

            return $response;
        } catch (\Exception $e) {
            error_log("Face login error: " . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to process face login: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calculate the Euclidean distance between two face descriptors.
     *
     * @param array $desc1 First descriptor (array of floats)
     * @param array $desc2 Second descriptor (array of floats)
     * @return float The Euclidean distance
     */
    private function calculateEuclideanDistance(array $desc1, array $desc2): float
    {
        if (count($desc1) !== count($desc2)) {
            error_log("Descriptor length mismatch: " . count($desc1) . " vs " . count($desc2));
            throw new \Exception('Face descriptors must have the same length');
        }

        $sum = 0.0;
        for ($i = 0; $i < count($desc1); $i++) {
            $diff = $desc1[$i] - $desc2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }
}