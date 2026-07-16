<?php

namespace App\Http\Requests\Feed;

use App\Checkers\PostChecker;
use App\ExceptionCodes\PostExceptionCode;
use App\Http\Requests\ApiRequest;

class StorePostRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(PostChecker $postChecker): array
    {
        return $postChecker->postRules();
    }

    /** @return array{body: string} */
    public function payload(): array
    {
        /** @var array{body: string} $validatedPostData */
        $validatedPostData = $this->safe()->only(['body']);

        return $validatedPostData;
    }

    protected function validationExceptionCode(): int
    {
        return PostExceptionCode::POST_DATA_INVALID;
    }
}
