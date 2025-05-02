<?php
// src/Service/HuggingFaceService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private $client;
    private $apiKey;
    private $model;

    public function __construct(HttpClientInterface $client, string $apiKey, string $model)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generateRecommendation(string $prompt): string
    {
        try {
            $response = $this->client->request(
                'POST',
                "https://api-inference.huggingface.co/models/{$this->model}",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => $prompt,
                        'parameters' => [
                            'max_new_tokens' => 200,
                            'temperature' => 0.7
                        ]
                    ],
                    'timeout' => 30
                ]
            );

            return $response->getContent();
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}