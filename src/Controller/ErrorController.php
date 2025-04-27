<?php

namespace App\Controller;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ErrorController
{
    public function show(Request $request, FlattenException $exception, ?DebugLoggerInterface $logger = null): JsonResponse
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage();

        return new JsonResponse([
            'error' => $message ?: 'An error occurred',
        ], $statusCode);
    }
}