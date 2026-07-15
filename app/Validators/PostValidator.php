<?php

namespace App\Validators;

use App\Constants\PostConstant;
use App\ExceptionCodes\PostExceptionCode;
use App\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\Validator;

class PostValidator
{
    /** @return array{body: string} */
    public function validatePost(array $input): array
    {
        return $this->validateBody(
            $input,
            PostConstant::BODY_MAX_LENGTH,
            PostExceptionCode::POST_DATA_INVALID,
        );
    }

    /** @return array{body: string} */
    public function validateComment(array $input): array
    {
        return $this->validateBody(
            $input,
            PostConstant::COMMENT_MAX_LENGTH,
            PostExceptionCode::COMMENT_DATA_INVALID,
        );
    }

    /** @return array{body: string} */
    private function validateBody(array $input, int $maxLength, int $exceptionCode): array
    {
        $validator = Validator::make($input, [
            'body' => ['required', 'string', 'max:'.$maxLength],
        ]);

        if ($validator->fails()) {
            throw new DomainValidationException(
                $validator->errors()->toArray(),
                $exceptionCode,
            );
        }

        /** @var array{body: string} $validated */
        $validated = $validator->validated();

        return $validated;
    }
}
