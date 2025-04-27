<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class BadWordsFilterService
{
    private HttpClientInterface $client;
    private string $geminiApiKey;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $client, string $geminiApiKey, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->geminiApiKey = $geminiApiKey;
        $this->logger = $logger;
    }

    /**
     * Analyse le texte pour détecter les mots inappropriés.
     * Retourne true si le texte contient des mots inappropriés, false sinon.
     */
    public function containsInappropriateWords(string $text): bool
{
    $this->logger->info('Checking text: {text}', ['text' => $text]);

    // Local detection

    // Gemini API detection
    try {
        $response = $this->client->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}", [
            'json' => [
                'contents' => [
                    ['parts' => [[
                        'text' => "Does the following text contain any inappropriate, vulgar, or offensive language? Respond with 'true' if it does, or 'false' if it does not. Do not modify the text, only return 'true' or 'false'. Examples of inappropriate language include words like 'fuck', 'shit', 'bitch', and phrases like 'fuck you', 'go to hell', etc. Text: \"$text\""
                    ]]]
                ]
            ]
        ]);

        $data = $response->toArray();
        $this->logger->info('Gemini API full response: {response}', ['response' => json_encode($data)]);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $this->logger->error('Unexpected Gemini API response structure: {response}', ['response' => json_encode($data)]);
            return true; // Fail-safe
        }

        $result = strtolower(trim($data['candidates'][0]['content']['parts'][0]['text']));
        $this->logger->info('Gemini API response for text "{text}": {result}', ['text' => $text, 'result' => $result]);
        return $result === 'true';
    } catch (\Exception $e) {
        $this->logger->error('Error calling Gemini API: {error}, Trace: {trace}', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return $this->containsInappropriateWordsLocally($text); // Fallback to local detection
    }
}

    /**
     * Détection locale des mots inappropriés.
     * Retourne true si le texte contient des mots ou expressions interdits, false sinon.
     */
    private function containsInappropriateWordsLocally(string $text): bool
    {
        // Liste de mots interdits
        $badWords = [
            'shit',  'asshole', 'bastard', 
            'connard', 'salope', 'pute', 'enculé', 'fils de pute'
        ];
        
        // Liste d'expressions interdites
        $badPhrases = [
             'go to hell', 'suck my', 'piece of shit', 'motherfucker',
            'va te faire', 'fils de p', 'encule toi', 'tg'
        ];

        $textLower = strtolower($text);
        
        // Vérifier les mots interdits
        foreach ($badWords as $word) {
            if (strpos($textLower, $word) !== false) {
                return true;
            }
        }

        // Vérifier les expressions interdites (en normalisant les espaces)
        $textNormalized = preg_replace('/\s+/', ' ', $textLower);
        foreach ($badPhrases as $phrase) {
            if (strpos($textNormalized, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }
}