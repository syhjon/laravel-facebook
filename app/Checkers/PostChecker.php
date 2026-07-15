<?php

namespace App\Checkers;

use App\Validators\PostValidator;

class PostChecker
{
    public function __construct(
        private readonly PostValidator $postValidator,
    ) {}

    /** @return array<string, mixed> */
    public function feedRules(): array
    {
        return $this->postValidator->feedRules();
    }

    /** @return array<string, mixed> */
    public function postRules(): array
    {
        return $this->postValidator->postRules();
    }

    /** @return array<string, mixed> */
    public function commentRules(): array
    {
        return $this->postValidator->commentRules();
    }
}
