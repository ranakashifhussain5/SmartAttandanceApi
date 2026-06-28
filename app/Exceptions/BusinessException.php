<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown by the service layer for domain-rule violations (e.g. session not
 * active, WiFi mismatch, duplicate attendance). Laravel calls render()
 * automatically, so controllers don't need try/catch boilerplate.
 */
class BusinessException extends Exception
{
    public function __construct(string $message, protected int $status = 422)
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}
