<?php

namespace App\Contracts\Responses;

use App\Constants\HttpCodeConstant;
use Illuminate\Http\JsonResponse;

interface ResponseMakerInterface
{
    /**
     * @param  array<string, mixed>  $additionalResponseData
     */
    public function createResponse(
        mixed $responseData = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $additionalResponseData = [],
    ): JsonResponse;

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $additionalResponseData
     */
    public function createResponseWithMetadata(
        mixed $responseData = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $metadata = [],
        array $additionalResponseData = [],
    ): JsonResponse;
}
