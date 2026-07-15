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

    public function make(
        mixed $data = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $additional = [],
    ): JsonResponse {
        return $this->json([
            ...$additional,
            'message' => $message ?: HttpCodeConstant::defaultMessage($httpCode),
            'data' => $data,
            'duration' => $this->duration(),
        ], $httpCode);
    }

    public function makeWithMeta(
        mixed $data = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $meta = [],
        array $additional = [],
    ): JsonResponse {
        return $this->json([
            ...$additional,
            'message' => $message ?: HttpCodeConstant::defaultMessage($httpCode),
            'data' => $data,
            'meta' => $meta,
            'duration' => $this->duration(),
            'datetime' => CarbonImmutable::createFromTimestamp($this->requestStartedAt())
                ->format(ResponseConstant::DATETIME_FORMAT),
        ], $httpCode);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload, int $httpCode): JsonResponse
    {
        return $this->responseFactory->json($payload, $httpCode);
    }

    private function duration(): float
    {
        return round(
            microtime(true) - $this->requestStartedAt(),
            ResponseConstant::DURATION_PRECISION,
        );
    }

    private function requestStartedAt(): float
    {
        if (defined('LARAVEL_START')) {
            return (float) constant('LARAVEL_START');
        }

        return (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    }
}
