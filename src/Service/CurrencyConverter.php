<?php
// src/Service/CurrencyConverter.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyConverter
{
    private $client;
    private $apiKey;
    
    public function __construct(HttpClientInterface $client, string $exchangeRateApiKey)
    {
        $this->client = $client;
        $this->apiKey = $exchangeRateApiKey;
    }
    
    public function getExchangeRates(string $baseCurrency = 'EUR'): array
    {
        try {
            $response = $this->client->request(
                'GET',
                "https://v6.exchangerate-api.com/v6/{$this->apiKey}/latest/{$baseCurrency}"
            );
            
            $data = $response->toArray();
            
            if ($data['result'] === 'success') {
                return $data['conversion_rates'];
            }
            
            return [];
        } catch (\Exception $e) {
            // Fallback rates if API fails
            return [
                'EUR' => 1,
                'USD' => 1.08,
                'GBP' => 0.85,
                'JPY' => 157.34,
                'CAD' => 1.47
            ];
        }
    }
    
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $rates = $this->getExchangeRates($fromCurrency);
        
        if (isset($rates[$toCurrency])) {
            return $amount * $rates[$toCurrency];
        }
        
        return $amount; // Return original if conversion fails
    }
}