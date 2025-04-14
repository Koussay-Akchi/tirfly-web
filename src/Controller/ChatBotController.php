<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChatBotController extends AbstractController
{
    private HttpClientInterface $client;

    // Define the API URL
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/chatbot', name: 'chatbot_home')]
    public function index(): Response
    {
        return $this->render('chatbot/index.html.twig');
    }

    #[Route('/chatbot/ask', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        try {
            // 1️⃣ Vérifier si la requête contient une question
            $data = json_decode($request->getContent(), true);
            $question = $data['question'] ?? '';

            if (empty($question)) {
                return new JsonResponse(['response' => 'Veuillez poser une question.'], Response::HTTP_BAD_REQUEST);
            }

            // 2️⃣ API Key - replace with the correct API Key.
            $apiKey = 'AIzaSyDpxgIqCekHbOLgUL6fyHdavfAGr1KijcQ';  // Make sure to keep this secure in production!

            // 3️⃣ Préparer la requête à l'API Gemini
            $payload = [
                'contents' => [
                    [
                        'parts' => [['text' => $question]]
                    ]
                ]
            ];

            // 4️⃣ Envoyer la requête à l'API Gemini
            $response = $this->client->request('POST', self::GEMINI_API_URL, [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => ['key' => $apiKey],
                'json' => $payload,
                'timeout' => 30
            ]);

            // 5️⃣ Vérifier le statut HTTP de la réponse
            $statusCode = $response->getStatusCode();
            $rawContent = $response->getContent(false);  // Get raw response to avoid errors

            // 6️⃣ Vérifier si la réponse est bien structurée
            $content = json_decode($rawContent, true);
            if ($statusCode !== 200 || !isset($content['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Réponse invalide de l\'API: ' . $rawContent);
            }

            $answer = $content['candidates'][0]['content']['parts'][0]['text'];

            return new JsonResponse(['response' => trim($answer)]);

        } catch (\Throwable $e) {
            return new JsonResponse(
                ['response' => 'Erreur technique: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    
}
