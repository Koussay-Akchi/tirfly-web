<?php

namespace App\Controller;

use App\Dto\PaymentRequest;
use App\Dto\PaymentResponse;
use App\Entity\Payment;
use App\Entity\Data;
use App\Entity\Reservation;
use App\Repository\ClientRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Uid\Uuid;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    private $paymeeApiUrl = 'https://sandbox.paymee.tn/api/v2/payments';
    private $apiToken = '1ebd9a7e4101918e9d1868fb9669b6a3c4657fc7';

    public function __construct(private LoggerInterface $logger)
    {
    }

    #[Route('/initiate/{id}', name: 'app_payment_initiate', methods: ['POST'])]
    public function initiatePayment(
        int $id,
        ReservationRepository $reservationRepository,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request
    ): JsonResponse {
        $this->logger->info('Initiating payment for reservation ID: ' . $id);
    
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            $this->logger->error('Reservation not found: ' . $id);
            return $this->json(['message' => 'Réservation introuvable.'], 404);
        }
    
        $client = $clientRepository->find($reservation->getClient()->getId());
        if (!$client) {
            $this->logger->error('Client not found for reservation: ' . $id);
            return $this->json(['message' => 'Client introuvable.'], 404);
        }
    
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setAmount($reservation->getPrixTotal());
        $paymentRequest->setNote('Payment for Reservation #' . $id);
        $paymentRequest->setFirstName($client->getPrenom() ?? 'Unknown');
        $paymentRequest->setLastName($client->getNom() ?? 'Unknown');
    
        // Validate and format email
        $email = $client->getEmail() ?? 'default@example.com';
        $paymentRequest->setEmail($email);
    
        // Format phone number to Tunisian standard (+216 followed by 8 digits)
        $phone = $client->getPhoneNumber() ?? '00000000';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) >= 8) {
            $phone = '+216' . substr($phone, -8);
        } else {
            $phone = '+216' . str_pad($phone, 8, '0', STR_PAD_LEFT);
        }
        $this->logger->info('Adjusted phone number: ' . $phone);
        $paymentRequest->setPhone($phone);
    
        // Set URLs (ensure these are accessible endpoints)
        $paymentRequest->setReturnUrl('https://127.0.0.1:8000/payment/return/' . $id);
        $paymentRequest->setCancelUrl('https://127.0.0.1:8000/payment/cancel');
        $paymentRequest->setWebhookUrl('https://127.0.0.1:8000/payment/webhook');
    
        // Generate a unique order ID
        $paymentRequest->setOrderId((string) Uuid::v4());
    
        $jsonRequest = $serializer->serialize(
            $paymentRequest,
            'json',
            ['groups' => 'payment_request:write', 'name_converter' => new CamelCaseToSnakeCaseNameConverter()]
        );
        $this->logger->info('Sent JSON Request: ' . $jsonRequest);
    
        $clientHttp = HttpClient::create();
        try {
            $response = $clientHttp->request('POST', $this->paymeeApiUrl . '/create', [
                'headers' => [
                    'Authorization' => 'token ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => $jsonRequest,
                'verify_peer' => false, // Temporarily disable SSL verification for testing
            ]);
    
            $this->logger->info('API Response Status: ' . $response->getStatusCode());
            $rawResponse = $response->getContent(false);
            $this->logger->info('Raw API Response: ' . $rawResponse);
    
            if ($response->getStatusCode() === 200) {
                $paymentResponse = $serializer->deserialize(
                    $rawResponse,
                    PaymentResponse::class,
                    'json',
                    [
                        'groups' => 'payment_response:read',
                        'name_converter' => new CamelCaseToSnakeCaseNameConverter(),
                    ]
                );
                $this->logger->info('Deserialized Response: ' . json_encode([
                    'status' => $paymentResponse->getStatus(),
                    'message' => $paymentResponse->getMessage(),
                    'code' => $paymentResponse->getCode(),
                    'data' => $paymentResponse->getData() ? $paymentResponse->getData()->getPaymentUrl() : null,
                ]));
    
                if ($paymentResponse->getStatus() && $paymentResponse->getData() && $paymentResponse->getData()->getPaymentUrl()) {
                    $reservation->setPaymentToken($paymentResponse->getData()->getToken());
                    $reservation->setStatut('EN_ATTENTE');
                    $entityManager->persist($reservation);
                    $entityManager->flush();
    
                    $this->logger->info('Payment token saved: ' . $paymentResponse->getData()->getToken());
                    return $this->json(['redirectUrl' => $paymentResponse->getData()->getPaymentUrl()]);
                } else {
                    $this->logger->error('Payment failed: ' . ($paymentResponse->getMessage() ?? 'No payment URL or invalid response'));
                    return $this->json(['message' => 'Échec du paiement : ' . ($paymentResponse->getMessage() ?? 'Réponse invalide')], 400);
                }
            } else {
                $this->logger->error('API Error: ' . $response->getStatusCode() . ' - ' . $rawResponse);
                return $this->json(['message' => 'Erreur API : ' . $response->getStatusCode() . ' - ' . $rawResponse], $response->getStatusCode());
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->json(['message' => 'Échec du paiement : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/return/{id}', name: 'app_payment_return', methods: ['GET'])]
    public function handlePaymentReturn(
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request
    ): Response {
        $this->logger->info('Handling payment return for reservation ID: ' . $id);

        $reservation = $reservationRepository->find($id);
        if (!$reservation || !$reservation->getPaymentToken()) {
            $this->logger->error('Reservation or payment token not found: ' . $id);
            $this->addFlash('error', 'Réservation ou token de paiement introuvable.');
            return $this->redirectToRoute('historique_reservations');
        }

        $client = HttpClient::create();
        try {
            $response = $client->request('GET', $this->paymeeApiUrl . '/' . $reservation->getPaymentToken() . '/check', [
                'headers' => [
                    'Authorization' => 'token ' . $this->apiToken,
                ],
            ]);

            $this->logger->info('Paymee check API response status: ' . $response->getStatusCode());
            $this->logger->debug('Paymee check API response content: ' . $response->getContent(false));

            if ($response->getStatusCode() === 200) {
                $paymentResponse = $serializer->deserialize(
                    $response->getContent(),
                    PaymentResponse::class,
                    'json',
                    ['groups' => 'payment_response:read', 'name_converter' => new CamelCaseToSnakeCaseNameConverter()]
                );
                $this->logger->info('Paymee check API response parsed: ' . json_encode([
                    'status' => $paymentResponse->getStatus(),
                    'message' => $paymentResponse->getMessage(),
                    'code' => $paymentResponse->getCode(),
                    'data' => [
                        'paymentStatus' => $paymentResponse->getData() ? $paymentResponse->getData()->getPaymentStatus() : null,
                    ],
                ]));

                if ($paymentResponse->getStatus() && $paymentResponse->getData() && $paymentResponse->getData()->getPaymentStatus() === true) {
                    $this->savePaymentToDatabase($reservation, $paymentResponse, $entityManager, $request, $response->getContent());
                    $this->addFlash('success', 'Paiement confirmé !');
                } else {
                    $this->logger->warning('Payment not confirmed: ' . ($paymentResponse->getData() ? $paymentResponse->getData()->getPaymentStatus() : 'No payment status'));
                    $this->addFlash('error', 'Paiement non confirmé.');
                }
            } else {
                $this->logger->error('Paymee check API error: ' . $response->getStatusCode() . ' - ' . $response->getContent(false));
                $this->addFlash('error', 'Erreur lors de la vérification du statut.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Payment check exception: ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('historique_reservations');
    }

    #[Route('/check/{id}', name: 'app_payment_check', methods: ['GET'])]
    public function checkPaymentStatus(
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $this->logger->info('Checking payment status for reservation ID: ' . $id);

        $reservation = $reservationRepository->find($id);
        if (!$reservation || !$reservation->getPaymentToken()) {
            $this->logger->error('Reservation or payment token not found: ' . $id);
            return $this->json(['success' => false, 'message' => 'Réservation ou token de paiement introuvable.'], 400);
        }

        $client = HttpClient::create();
        try {
            $response = $client->request('GET', $this->paymeeApiUrl . '/' . $reservation->getPaymentToken() . '/check', [
                'headers' => [
                    'Authorization' => 'token ' . $this->apiToken,
                ],
            ]);

            $this->logger->info('Paymee check API response status: ' . $response->getStatusCode());
            $this->logger->debug('Paymee check API response content: ' . $response->getContent(false));

            if ($response->getStatusCode() === 200) {
                $paymentResponse = $serializer->deserialize(
                    $response->getContent(),
                    PaymentResponse::class,
                    'json',
                    ['groups' => 'payment_response:read', 'name_converter' => new CamelCaseToSnakeCaseNameConverter()]
                );
                $this->logger->info('Paymee check API response parsed: ' . json_encode([
                    'status' => $paymentResponse->getStatus(),
                    'message' => $paymentResponse->getMessage(),
                    'code' => $paymentResponse->getCode(),
                    'data' => [
                        'paymentStatus' => $paymentResponse->getData() ? $paymentResponse->getData()->getPaymentStatus() : null,
                    ],
                ]));

                if ($paymentResponse->getStatus() && $paymentResponse->getData() && $paymentResponse->getData()->getPaymentStatus() === true) {
                    $this->savePaymentToDatabase($reservation, $paymentResponse, $entityManager, null, $response->getContent());
                    return $this->json(['success' => true, 'message' => 'Paiement confirmé.']);
                } else {
                    $this->logger->warning('Payment not confirmed: ' . ($paymentResponse->getData() ? $paymentResponse->getData()->getPaymentStatus() : 'No payment status'));
                    return $this->json(['success' => false, 'message' => 'Paiement non confirmé.'], 400);
                }
            } else {
                $this->logger->error('Paymee check API error: ' . $response->getStatusCode() . ' - ' . $response->getContent(false));
                return $this->json(['success' => false, 'message' => 'Erreur lors de la vérification du statut.'], $response->getStatusCode());
            }
        } catch (\Exception $e) {
            $this->logger->error('Payment check exception: ' . $e->getMessage(), ['exception' => $e]);
            return $this->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/webhook', name: 'app_payment_webhook', methods: ['POST'])]
    public function handleWebhook(
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $this->logger->info('Webhook received: ' . $request->getContent());

        $payload = $request->getContent();
        try {
            $paymentResponse = $serializer->deserialize(
                $payload,
                PaymentResponse::class,
                'json',
                ['groups' => 'payment_response:read', 'name_converter' => new CamelCaseToSnakeCaseNameConverter()]
            );
            $reservation = $reservationRepository->findOneBy(['paymentToken' => $paymentResponse->getData()->getToken()]);

            if ($reservation && $paymentResponse->getStatus() && $paymentResponse->getData()->getPaymentStatus() === true) {
                $this->savePaymentToDatabase($reservation, $paymentResponse, $entityManager, null, $payload);
                $this->logger->info('Webhook processed successfully for reservation: ' . $reservation->getId());
                return $this->json(['success' => true, 'message' => 'Webhook traité avec succès.']);
            } else {
                $this->logger->warning('Webhook failed: Invalid payment status or reservation not found.');
                return $this->json(['success' => false, 'message' => 'Paiement non confirmé ou réservation introuvable.'], 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Webhook exception: ' . $e->getMessage(), ['exception' => $e]);
            return $this->json(['success' => false, 'message' => 'Erreur lors du traitement du webhook : ' . $e->getMessage()], 500);
        }
    }

    private function savePaymentToDatabase(
        Reservation $reservation,
        PaymentResponse $paymentResponse,
        EntityManagerInterface $entityManager,
        ?Request $request = null,
        ?string $rawResponse = null
    ): void {
        $this->logger->info('Saving payment to database for reservation: ' . $reservation->getId());

        $data = new Data();
        $data->setAmount($paymentResponse->getData()->getAmount() ?? 0.0);
        $data->setEmail($paymentResponse->getData()->getEmail());
        $data->setFirstname($paymentResponse->getData()->getFirstName());
        $data->setLastname($paymentResponse->getData()->getLastName());
        $data->setPhone($paymentResponse->getData()->getPhone());
        $data->setNote($paymentResponse->getData()->getNote());
        $data->setPaymentStatus($paymentResponse->getData()->getPaymentStatus() ?? false);
        $data->setToken($paymentResponse->getData()->getToken());
        $data->setBuyerId($reservation->getClient()->getId());
        $data->setReceivedAmount((int)(($paymentResponse->getData()->getAmount() ?? 0.0) * 100));

        // Use transaction_id from raw response or request query parameter
        $transactionId = null;
        if ($rawResponse) {
            $rawData = json_decode($rawResponse, true);
            $transactionId = $rawData['data']['transaction_id'] ?? null;
        }
        if (!$transactionId && $request) {
            $transactionId = $request->query->getInt('transaction');
        }
        $data->setTransactionId($transactionId ?? mt_rand(100000, 999999));

        // Set cost field if available in raw response
        if ($rawResponse) {
            $rawData = json_decode($rawResponse, true);
            $data->setCost($rawData['data']['cost'] ?? 0.0);
        }

        $payment = new Payment();
        $payment->setClient($reservation->getClient());
        $payment->setCode($paymentResponse->getCode());
        $payment->setMessage($paymentResponse->getMessage());
        $payment->setStatus($paymentResponse->getStatus() ? 'SUCCESS' : 'FAILED');
        $payment->setDateSubmit(new \DateTime());
        $payment->setData($data);
        $payment->setReservation($reservation);

        $data->setPayment($payment);
        $reservation->setPayment($payment);
        $reservation->setStatut('CONFIRME');

        $entityManager->persist($data);
        $entityManager->persist($payment);
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->logger->info('Payment saved successfully for reservation: ' . $reservation->getId());
    }
}