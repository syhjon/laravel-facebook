<?php

namespace App\Checkers;

use App\Validators\PostValidator;

class PostChecker
{
    public function __construct(
        private readonly PostValidator $postValidator,
    ) {}

    /** @return array{body: string} */
    public function checkPost(array $input): array
    {
        return $this->postValidator->validatePost($input);
    }

    /** @return array{body: string} */
    public function checkComment(array $input): array
    {
        return $this->postValidator->validateComment($input);
    }
}
