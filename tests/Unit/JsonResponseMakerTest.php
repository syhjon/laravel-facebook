<?php

namespace Tests\Unit;

use App\Constants\HttpCodeConstant;
use App\Contracts\Responses\ResponseMakerInterface;
use Tests\TestCase;

class JsonResponseMakerTest extends TestCase
{
    public function test_it_makes_a_consistent_json_response(): void
    {
        $jsonResponse = $this->app->make(ResponseMakerInterface::class)->createResponse(
            responseData: ['id' => 1],
            httpCode: HttpCodeConstant::CREATED,
            additionalResponseData: ['redirect' => '/dashboard'],
        );

        $responsePayload = $jsonResponse->getData(true);

        $this->assertSame(HttpCodeConstant::CREATED, $jsonResponse->getStatusCode());
        $this->assertSame('application/json', $jsonResponse->headers->get('Content-Type'));
        $this->assertSame('Created', $responsePayload['message']);
        $this->assertSame(['id' => 1], $responsePayload['data']);
        $this->assertSame('/dashboard', $responsePayload['redirect']);
        $this->assertIsFloat($responsePayload['duration']);
    }

    public function test_it_makes_a_meta_response_without_allowing_reserved_fields_to_be_overridden(): void
    {
        $jsonResponse = $this->app->make(ResponseMakerInterface::class)->createResponseWithMetadata(
            responseData: [['id' => 1]],
            metadata: ['has_more' => false],
            additionalResponseData: [
                'message' => '不得覆寫',
                'data' => [],
                'meta' => [],
            ],
        );

        $responsePayload = $jsonResponse->getData(true);

        $this->assertSame('OK', $responsePayload['message']);
        $this->assertSame([['id' => 1]], $responsePayload['data']);
        $this->assertSame(['has_more' => false], $responsePayload['meta']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $responsePayload['datetime'],
        );
    }
}
