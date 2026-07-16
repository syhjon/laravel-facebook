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
        /** @var array{cursor?: string|null} $validatedFeedQuery */
        $validatedFeedQuery = $this->safe()->only(['cursor']);
        $paginationCursor = $validatedFeedQuery['cursor'] ?? null;

        return is_string($paginationCursor) && $paginationCursor !== '' ? $paginationCursor : null;
    }

    protected function validationExceptionCode(): int
    {
        return PostExceptionCode::FEED_QUERY_INVALID;
    }
}
