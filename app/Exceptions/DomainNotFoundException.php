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

    public function render(Request $httpRequest): JsonResponse
    {
        return resolve(ResponseMakerInterface::class)->createResponse(
            httpCode: HttpCodeConstant::NOT_FOUND,
            message: $this->getMessage(),
            additionalResponseData: ['code' => $this->exceptionCode],
        );
    }
}
