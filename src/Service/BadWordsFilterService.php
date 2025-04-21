<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BadWordsFilterService
{
    private HttpClientInterface $client;
    private string $geminiApiKey;

    public function __construct(HttpClientInterface $client, string $geminiApiKey)
    {
        $this->client = $client;
        $this->geminiApiKey = $geminiApiKey;
    }

    public function analyzeText(string $text): string
    {
        $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
            'query' => ['key' => $this->geminiApiKey],
            'json' => [
                'contents' => [
                    ['parts' => [['text' => "Cleansing bad words from this text, return the cleaned version only: \"$text\""]]]
                ]
            ]
        ]);

        $data = $response->toArray();

        // Gemini returns results in `candidates[0].content.parts[0].text`
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? $text;
    }
}
