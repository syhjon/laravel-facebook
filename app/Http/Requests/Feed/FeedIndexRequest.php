<?php

namespace App\Http\Requests\Feed;

use App\Checkers\PostChecker;
use App\ExceptionCodes\PostExceptionCode;
use App\Http\Requests\ApiRequest;

class FeedIndexRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(PostChecker $postChecker): array
    {
        return $postChecker->feedRules();
    }

    public function cursor(): ?string
    {
        /** @var array{cursor?: string|null} $validated */
        $validated = $this->safe()->only(['cursor']);
        $cursor = $validated['cursor'] ?? null;

        return is_string($cursor) && $cursor !== '' ? $cursor : null;
    }

    protected function validationExceptionCode(): int
    {
        return PostExceptionCode::FEED_QUERY_INVALID;
    }
}
