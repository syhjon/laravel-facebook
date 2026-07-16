<?php

namespace App\Exceptions;

use App\Constants\HttpCodeConstant;
use App\Contracts\Responses\ResponseMakerInterface;
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

    public function render(Request $httpRequest): JsonResponse
    {
        return resolve(ResponseMakerInterface::class)->createResponse(
            httpCode: HttpCodeConstant::UNPROCESSABLE_ENTITY,
            message: $this->getMessage(),
            additionalResponseData: [
                'code' => $this->exceptionCode,
                'errors' => $this->errors,
            ],
        );
    }
}
