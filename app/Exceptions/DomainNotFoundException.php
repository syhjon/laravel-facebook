<?php

namespace App\Exceptions;

use App\Constants\HttpCodeConstant;
use App\Contracts\Responses\ResponseMakerInterface;
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
        return resolve(ResponseMakerInterface::class)->make(
            httpCode: HttpCodeConstant::NOT_FOUND,
            message: $this->getMessage(),
            additional: ['code' => $this->exceptionCode],
        );
    }
}
