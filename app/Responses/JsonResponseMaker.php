<?php

namespace App\Responses;

use App\Constants\HttpCodeConstant;
use App\Constants\ResponseConstant;
use App\Contracts\Responses\ResponseMakerInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;

class JsonResponseMaker implements ResponseMakerInterface
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
    ) {}

    public function createResponse(
        mixed $responseData = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $additionalResponseData = [],
    ): JsonResponse {
        return $this->createJsonResponse([
            ...$additionalResponseData,
            'message' => $message ?: HttpCodeConstant::defaultMessage($httpCode),
            'data' => $responseData,
            'duration' => $this->calculateRequestDuration(),
        ], $httpCode);
    }

    public function createResponseWithMetadata(
        mixed $responseData = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $metadata = [],
        array $additionalResponseData = [],
    ): JsonResponse {
        return $this->createJsonResponse([
            ...$additionalResponseData,
            'message' => $message ?: HttpCodeConstant::defaultMessage($httpCode),
            'data' => $responseData,
            'meta' => $metadata,
            'duration' => $this->calculateRequestDuration(),
            'datetime' => CarbonImmutable::createFromTimestamp($this->requestStartTimestamp())
                ->format(ResponseConstant::DATETIME_FORMAT),
        ], $httpCode);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createJsonResponse(array $responsePayload, int $httpCode): JsonResponse
    {
        return $this->responseFactory->json($responsePayload, $httpCode);
    }

    private function calculateRequestDuration(): float
    {
        return round(
            microtime(true) - $this->requestStartTimestamp(),
            ResponseConstant::DURATION_PRECISION,
        );
    }

    private function requestStartTimestamp(): float
    {
        if (defined('LARAVEL_START')) {
            return (float) constant('LARAVEL_START');
        }

        return (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    }
}
