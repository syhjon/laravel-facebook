<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DomainNotFoundException extends RuntimeException
{
    public function __construct(
        private readonly int $exceptionCode,
        string $message,
    ) {
        parent::__construct($message, $exceptionCode);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->exceptionCode,
        ], 404);
    }
}
