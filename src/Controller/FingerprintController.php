<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\FingerprintSession;
use App\Entity\User;
use App\Repository\FingerprintSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;

class FingerprintController extends AbstractController
{
    private FingerprintSessionRepository $fingerprintSessionRepository;

    public function __construct(FingerprintSessionRepository $fingerprintSessionRepository)
    {
        $this->fingerprintSessionRepository = $fingerprintSessionRepository;
    }

    #[Route('/api/fingerprint/qr', name: 'api_fingerprint_qr', methods: ['POST'])]
    public function generateFingerprintQrCode(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
    
        if (!$email) {
            return new JsonResponse(['error' => 'Email required'], 400);
        }
    
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if (!$user->getFingerprintTemplate()) {
            return new JsonResponse(['error' => 'No fingerprint enrolled for this user'], 400);
        }
    
        $token = bin2hex(random_bytes(16));
        $fingerprintSession = new FingerprintSession();
        $fingerprintSession->setToken($token);
        $fingerprintSession->setEmail($email);
        $fingerprintSession->setStatus('pending');
        $fingerprintSession->setExpiresAt(new \DateTime('+5 minutes'));
    
        try {
            $this->fingerprintSessionRepository->save($fingerprintSession);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to save fingerprint session: ' . $e->getMessage()], 500);
        }
    
        try {
            $verifyUrl = $this->generateUrl('fingerprint_verify', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            
            $client = HttpClient::create(['timeout' => 10]);
            $response = $client->request('GET', 'https://api.qrserver.com/v1/create-qr-code/', [
                'query' => [
                    'data' => $verifyUrl,
                    'size' => '200x200',
                    'format' => 'png',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('QR code API returned status ' . $response->getStatusCode() . ': ' . $response->getContent(false));
            }

            $qrCodeImage = $response->getContent();
            $qrCodeBase64 = base64_encode($qrCodeImage);

            return new JsonResponse([
                'token' => $token,
                'qrCode' => 'data:image/png;base64,' . $qrCodeBase64
            ], 200);
        } catch (TransportException $e) {
            return new JsonResponse(['error' => 'Failed to connect to QR code API: ' . $e->getMessage()], 503);
        } catch (ClientException $e) {
            return new JsonResponse(['error' => 'QR code API client error: ' . $e->getMessage()], 400);
        } catch (ServerException $e) {
            return new JsonResponse(['error' => 'QR code API server error: ' . $e->getMessage()], 503);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to generate QR code: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/fingerprint/enroll', name: 'api_fingerprint_enroll', methods: ['POST'])]
    public function generateFingerprintEnrollQrCode(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email required'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if ($user->getFingerprintTemplate()) {
            return new JsonResponse(['error' => 'Fingerprint already enrolled'], 400);
        }

        $token = bin2hex(random_bytes(16));
        $fingerprintSession = new FingerprintSession();
        $fingerprintSession->setToken($token);
        $fingerprintSession->setEmail($email);
        $fingerprintSession->setStatus('pending');
        $fingerprintSession->setExpiresAt(new \DateTime('+5 minutes'));

        try {
            $this->fingerprintSessionRepository->save($fingerprintSession);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to save fingerprint session: ' . $e->getMessage()], 500);
        }

        try {
            $verifyUrl = $this->generateUrl('fingerprint_enroll_verify', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            
            $client = HttpClient::create(['timeout' => 10]);
            $response = $client->request('GET', 'https://api.qrserver.com/v1/create-qr-code/', [
                'query' => [
                    'data' => $verifyUrl,
                    'size' => '200x200',
                    'format' => 'png',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('QR code API returned status ' . $response->getStatusCode() . ': ' . $response->getContent(false));
            }

            $qrCodeImage = $response->getContent();
            $qrCodeBase64 = base64_encode($qrCodeImage);

            return new JsonResponse([
                'token' => $token,
                'qrCode' => 'data:image/png;base64,' . $qrCodeBase64
            ], 200);
        } catch (TransportException $e) {
            return new JsonResponse(['error' => 'Failed to connect to QR code API: ' . $e->getMessage()], 503);
        } catch (ClientException $e) {
            return new JsonResponse(['error' => 'QR code API client error: ' . $e->getMessage()], 400);
        } catch (ServerException $e) {
            return new JsonResponse(['error' => 'QR code API server error: ' . $e->getMessage()], 503);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to generate QR code: ' . $e->getMessage()], 500);
        }
    }
    
    #[Route('/fingerprint/verify/{token}', name: 'fingerprint_verify', methods: ['GET'])]
    public function fingerprintVerifyPage(string $token, EntityManagerInterface $em): Response
    {
        $session = $this->fingerprintSessionRepository->findOneByToken($token);
        if (!$session) {
            return new Response('Invalid or expired session', 404);
        }

        if ($session->getExpiresAt() < new \DateTime()) {
            return new Response('Session expired', 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $session->getEmail()]);
        if (!$user || !$user->getCredentialId()) {
            return new Response('User not found or no credential enrolled', 404);
        }

        return $this->render('fingerprint/verify.html.twig', [
            'token' => $token,
            'email' => $session->getEmail(),
            'credentialId' => $user->getCredentialId()
        ]);
    }

    #[Route('/fingerprint/enroll/verify/{token}', name: 'fingerprint_enroll_verify', methods: ['GET'])]
    public function fingerprintEnrollVerifyPage(string $token): Response
    {
        $session = $this->fingerprintSessionRepository->findOneByToken($token);
        if (!$session) {
            return new Response('Invalid or expired session', 404);
        }

        if ($session->getExpiresAt() < new \DateTime()) {
            return new Response('Session expired', 400);
        }

        return $this->render('fingerprint/enroll.html.twig', [
            'token' => $token,
            'email' => $session->getEmail()
        ]);
    }

    #[Route('/api/fingerprint/verify', name: 'api_fingerprint_verify', methods: ['POST'])]
    public function verifyFingerprint(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $fingerprintTemplate = $data['fingerprintTemplate'] ?? null;

        if (!$token) {
            return new JsonResponse(['error' => 'Token required'], 400);
        }

        if (!$fingerprintTemplate) {
            return new JsonResponse(['error' => 'Fingerprint template required'], 400);
        }

        $session = $this->fingerprintSessionRepository->findOneByToken($token);
        if (!$session) {
            return new JsonResponse(['error' => 'Invalid or expired session'], 404);
        }

        if ($session->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Session expired'], 400);
        }

        if ($session->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'Session already processed'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $session->getEmail()]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if (!$user->getFingerprintTemplate()) {
            return new JsonResponse(['error' => 'No fingerprint enrolled for this user'], 400);
        }

        if (!$this->compareFingerprintTemplates($user->getFingerprintTemplate(), $fingerprintTemplate)) {
            $session->setStatus('failed');
            $this->fingerprintSessionRepository->save($session);
            return new JsonResponse(['error' => 'Fingerprint does not match the enrolled fingerprint'], 400);
        }

        $session->setStatus('success');
        $this->fingerprintSessionRepository->save($session);

        return new JsonResponse(['message' => 'Fingerprint verified successfully'], 200);
    }

    private function compareFingerprintTemplates(array $template1, array $template2): bool
    {
        if (count($template1) !== count($template2)) {
            return false;
        }

        $threshold = 0.25; // Increased for more lenient matching
        $distance = 0.0;

        for ($i = 0; $i < count($template1); $i++) {
            $distance += pow($template1[$i] - $template2[$i], 2);
        }

        $distance = sqrt($distance);
        return $distance < $threshold;
    }

    #[Route('/api/fingerprint/enroll/verify', name: 'api_fingerprint_enroll_verify', methods: ['POST'])]
    public function verifyFingerprintEnrollment(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $success = $data['success'] ?? false;
        $fingerprintTemplate = $data['fingerprintTemplate'] ?? null;
        $credentialId = $data['credentialId'] ?? null;

        if (!$token) {
            return new JsonResponse(['error' => 'Token required'], 400);
        }

        if ($success && (!$fingerprintTemplate || !$credentialId)) {
            return new JsonResponse(['error' => 'Fingerprint template and credential ID required for successful enrollment'], 400);
        }

        $session = $this->fingerprintSessionRepository->findOneByToken($token);
        if (!$session) {
            return new JsonResponse(['error' => 'Invalid or expired session'], 404);
        }

        if ($session->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Session expired'], 400);
        }

        if ($session->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'Session already processed'], 400);
        }

        if ($success) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $session->getEmail()]);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            try {
                $user->setFingerprintTemplate($fingerprintTemplate);
                $user->setCredentialId($credentialId);
                $em->persist($user);
                $em->flush();
                $session->setStatus('success');
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Failed to save fingerprint template: ' . $e->getMessage()], 500);
            }
        } else {
            $session->setStatus('failed');
        }

        $this->fingerprintSessionRepository->save($session);

        return new JsonResponse(['message' => $success ? 'Fingerprint enrolled successfully' : 'Fingerprint enrollment failed'], 200);
    }

    #[Route('/api/fingerprint/status/{token}', name: 'api_fingerprint_status', methods: ['GET'])]
    public function checkFingerprintStatus(
        string $token,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $session = $this->fingerprintSessionRepository->findOneByToken($token);
        if (!$session) {
            return new JsonResponse(['error' => 'Invalid or expired session'], 404);
        }

        if ($session->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Session expired'], 400);
        }

        if ($session->getStatus() === 'pending') {
            return new JsonResponse(['status' => 'pending'], 200);
        }

        if ($session->getStatus() === 'failed') {
            return new JsonResponse(['error' => 'Fingerprint verification failed'], 401);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $session->getEmail()]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $tokenValue = $jwtManager->create($user);

        $response = new JsonResponse(['status' => 'success', 'message' => 'Fingerprint login successful']);
        $cookie = new Cookie(
            'BEARER',
            $tokenValue,
            time() + 3600 * 24,
            '/',
            null,
            false,
            true
        );
        $response->headers->setCookie($cookie);

        $this->fingerprintSessionRepository->remove($session);

        return $response;
    }

    #[Route('/api/fingerprint/enroll/status/{token}', name: 'api_fingerprint_enroll_status', methods: ['GET'])]
    public function checkFingerprintEnrollStatus(string $token): JsonResponse
    {
        $session = $this->fingerprintSessionRepository->findOneByToken($token);
        if (!$session) {
            return new JsonResponse(['error' => 'Invalid or expired session'], 404);
        }

        if ($session->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Session expired'], 400);
        }

        if ($session->getStatus() === 'pending') {
            return new JsonResponse(['status' => 'pending'], 200);
        }

        if ($session->getStatus() === 'failed') {
            return new JsonResponse(['error' => 'Fingerprint enrollment failed'], 401);
        }

        $this->fingerprintSessionRepository->remove($session);

        return new JsonResponse(['status' => 'success', 'message' => 'Fingerprint enrollment successful']);
    }
}