<?php
// src/Service/StockMonitor.php
namespace App\Service;

use App\Entity\Produit;
use Psr\Log\LoggerInterface;

class StockMonitor
{
    private $smsService;
    private $adminPhone;
    private $lowStockThreshold;
    private $logger;

    public function __construct(
        TwilioSmsService $smsService,
        string $adminPhone,
        LoggerInterface $logger,
        int $lowStockThreshold = 10
    ) {
        $this->smsService = $smsService;
        $this->adminPhone = $adminPhone;
        $this->logger = $logger;
        $this->lowStockThreshold = $lowStockThreshold;
    }

    public function checkLowStock(Produit $produit): void
    {
        $currentStock = $produit->getQuantiteStock();
        
        $this->logger->debug('Checking stock level', [
            'product' => $produit->getNom(),
            'current_stock' => $currentStock
        ]);

        if ($currentStock <= $this->lowStockThreshold) {
            $cleanNumber = $this->formatTunisianNumber($this->adminPhone);
            
            $this->logger->info('Low stock alert triggered', [
                'product' => $produit->getNom(),
                'phone' => $cleanNumber
            ]);

            try {
                $this->smsService->sendLowStockAlert(
                    $produit->getNom(),
                    $cleanNumber
                );
                $this->logger->info('Alert sent successfully');
            } catch (\Exception $e) {
                $this->logger->error('Failed to send alert', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function formatTunisianNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        return match(true) {
            str_starts_with($number, '216') => '+'.$number,
            str_starts_with($number, '0') => '+216'.substr($number, 1),
            default => '+216'.$number
        };
    }
}