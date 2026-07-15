<?php

namespace App\Contracts\Responses;

use App\Constants\HttpCodeConstant;
use Illuminate\Http\JsonResponse;

interface ResponseMakerInterface
{
    /**
     * @param  array<string, mixed>  $additional
     */
    public function make(
        mixed $data = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $additional = [],
    ): JsonResponse;

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $additional
     */
    public function makeWithMeta(
        mixed $data = null,
        int $httpCode = HttpCodeConstant::OK,
        ?string $message = null,
        array $meta = [],
        array $additional = [],
    ): JsonResponse;
}
