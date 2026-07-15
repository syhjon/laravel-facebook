<?php

namespace App\Http\Requests\Feed;

use App\Checkers\PostChecker;
use App\ExceptionCodes\PostExceptionCode;
use App\Http\Requests\ApiRequest;

class StoreCommentRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(PostChecker $postChecker): array
    {
        return $postChecker->commentRules();
    }

    public function body(): string
    {
        return (string) $this->validated('body');
    }

    protected function validationExceptionCode(): int
    {
        return PostExceptionCode::COMMENT_DATA_INVALID;
    }
}
