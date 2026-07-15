<?php

namespace App\Validators;

use App\Constants\PostConstant;

class PostValidator
{
    /** @return array<string, mixed> */
    public function feedRules(): array
    {
        return [
            'cursor' => ['nullable', 'string', 'max:'.PostConstant::CURSOR_MAX_LENGTH],
        ];
    }

    /** @return array<string, mixed> */
    public function postRules(): array
    {
        return $this->bodyRules(PostConstant::BODY_MAX_LENGTH);
    }

    /** @return array<string, mixed> */
    public function commentRules(): array
    {
        return $this->bodyRules(PostConstant::COMMENT_MAX_LENGTH);
    }

    /** @return array<string, mixed> */
    private function bodyRules(int $maxLength): array
    {
        return [
            'body' => ['required', 'string', 'max:'.$maxLength],
        ];
    }
}
