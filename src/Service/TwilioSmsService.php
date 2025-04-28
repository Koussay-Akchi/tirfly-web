<?php
// src/Service/TwilioSmsService.php
namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class TwilioSmsService
{
    private $client;
    private $twilioNumber;
    private $logger;

    public function __construct(
        string $accountSid, 
        string $authToken,
        string $twilioNumber,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->twilioNumber = $twilioNumber;
        
        try {
            $this->client = new Client($accountSid, $authToken);
            $this->logger->info('Twilio initialized successfully');
        } catch (\Exception $e) {
            $this->logger->error('Twilio init failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function sendLowStockAlert(string $productName, string $toNumber): bool
    {
        $this->logger->info("Attempting to send alert for {$productName} to {$toNumber}");
        
        try {
            $message = $this->client->messages->create(
                'whatsapp:'.$this->formatNumber($toNumber),
                [
                    'from' => 'whatsapp:'.$this->twilioNumber,
                    'body' => "🛒 ALERTE STOCK: '{$productName}' est en stock faible"
                ]
            );

            $this->logger->info("Message SID: {$message->sid}");
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error("Twilio error: ".$e->getMessage());
            return false;
        }
    }

    private function formatNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        return match(true) {
            str_starts_with($number, '216') => '+'.$number,
            str_starts_with($number, '0') => '+216'.substr($number, 1),
            default => '+216'.$number
        };
    }
}