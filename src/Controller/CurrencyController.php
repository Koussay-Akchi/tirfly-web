<?php
// src/Controller/CurrencyController.php

namespace App\Controller;

use App\Service\CurrencyConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends AbstractController
{
    #[Route('/api/convert-currency', name: 'convert_currency', methods: ['GET'])]
    public function convertCurrency(Request $request, CurrencyConverter $converter): JsonResponse
    {
        $amount = $request->query->get('amount', 0);
        $from = $request->query->get('from', 'EUR');
        $to = $request->query->get('to', 'USD');
        
        $convertedAmount = $converter->convert((float)$amount, $from, $to);
        
        return $this->json([
            'amount' => $convertedAmount,
            'currency' => $to,
            'symbol' => $this->getCurrencySymbol($to)
        ]);
    }
    
    private function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => '$'
        ];
        
        return $symbols[$currency] ?? $currency;
    }
}