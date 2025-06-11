<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SentimentAnalysisService
{

// !!!
// 11/06/2025 Koussay : All API keys have been revoked and removed from this file, any key found in older commits will also not work ###
// !!!

    private $httpClient;
    private $apiKey;
    private $logger;

    /**
     * Constructeur avec injection des dépendances.
     *
     * @param HttpClientInterface $httpClient Client HTTP pour appeler l'API
     * @param LoggerInterface $logger Logger pour enregistrer les informations de débogage
     * @param string $apiKey Clé API Hugging Face (remplacez par une clé valide)
     */
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $apiKey = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * Analyse le sentiment d'un texte et détermine l'urgence.
     *
     * @param string $text Texte de la réclamation à analyser
     * @return array Résultat avec 'urgence', 'label' et 'score' (ou 'error' en cas d'échec)
     */
    public function analyzeSentiment(string $text): array
    {
        // Liste de mots clés indiquant une urgence grave
        $keywords = ['inacceptable', 'honte', 'soufflé', 'grave', 'totalement', 'furieux'];
        $textLower = strtolower($text);
        $containsKeywords = false;

        // Vérification manuelle des mots clés
        foreach ($keywords as $keyword) {
            if (str_contains($textLower, $keyword)) {
                $containsKeywords = true;
                break;
            }
        }

        // Si des mots clés sont détectés, urgence définie comme "Inacceptable"
        if ($containsKeywords) {
            $this->logger->info('Urgence détectée manuellement pour le texte: ' . $text);
            return ['urgence' => 'Inacceptable', 'label' => 'manual', 'score' => 1.0];
        }

        try {
            // Appel à l'API Hugging Face pour l'analyse de sentiment
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/cmarkea/distilcamembert-base-sentiment', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['inputs' => $text],
            ]);

            $data = $response->toArray();
            $this->logger->info('Réponse API pour le texte: ' . $text, ['data' => $data]);

            // Vérification de la validité de la réponse
            if (!isset($data[0]) || !is_array($data[0])) {
                $this->logger->warning('Réponse API invalide pour le texte: ' . $text);
                return ['urgence' => 'Normal', 'error' => 'Réponse API invalide'];
            }

            // Extraction du label et du score
            $label = $data[0][0]['label'] ?? '3_stars';
            $score = $data[0][0]['score'] ?? 0;

            // Logique de classification de l'urgence
            if ($label === '1_star' && $score > 0.7) {
                return ['urgence' => 'Inacceptable', 'label' => $label, 'score' => $score];
            } elseif ($label === '1_star' || $label === '2_stars' || ($label === '3_stars' && $score < 0.5)) {
                return ['urgence' => 'Urgent', 'label' => $label, 'score' => $score];
            } else {
                return ['urgence' => 'Normal', 'label' => $label, 'score' => $score];
            }
        } catch (\Exception $e) {
            // Gestion des erreurs d'appel à l'API
            $this->logger->error('Erreur lors de l\'appel à l\'API pour le texte: ' . $text, ['exception' => $e]);
            return ['urgence' => 'Normal', 'error' => $e->getMessage()];
        }
    }
}