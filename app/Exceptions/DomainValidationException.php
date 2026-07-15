<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DomainValidationException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        private readonly array $errors,
        private readonly int $exceptionCode,
        string $message = '輸入資料有誤。',
    ) {
        parent::__construct($message, $exceptionCode);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->exceptionCode,
            'errors' => $this->errors,
        ], 422);
    }
}
