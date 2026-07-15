<?php

namespace Tests\Unit;

use App\Constants\HttpCodeConstant;
use App\Contracts\Responses\ResponseMakerInterface;
use Tests\TestCase;

class JsonResponseMakerTest extends TestCase
{
    public function test_it_makes_a_consistent_json_response(): void
    {
        $response = $this->app->make(ResponseMakerInterface::class)->make(
            data: ['id' => 1],
            httpCode: HttpCodeConstant::CREATED,
            additional: ['redirect' => '/dashboard'],
        );

        $payload = $response->getData(true);

        $this->assertSame(HttpCodeConstant::CREATED, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('Created', $payload['message']);
        $this->assertSame(['id' => 1], $payload['data']);
        $this->assertSame('/dashboard', $payload['redirect']);
        $this->assertIsFloat($payload['duration']);
    }

    public function test_it_makes_a_meta_response_without_allowing_reserved_fields_to_be_overridden(): void
    {
        $response = $this->app->make(ResponseMakerInterface::class)->makeWithMeta(
            data: [['id' => 1]],
            meta: ['has_more' => false],
            additional: [
                'message' => '不得覆寫',
                'data' => [],
                'meta' => [],
            ],
        );

        $payload = $response->getData(true);

        $this->assertSame('OK', $payload['message']);
        $this->assertSame([['id' => 1]], $payload['data']);
        $this->assertSame(['has_more' => false], $payload['meta']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $payload['datetime'],
        );
    }
}
