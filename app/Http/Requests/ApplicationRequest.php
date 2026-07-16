<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function authenticatedUser(): Authenticatable
    {
        /** @var Authenticatable $authenticatedUser */
        $authenticatedUser = $this->user();

        return $authenticatedUser;
    }

    public function userId(): int
    {
        return (int) $this->authenticatedUser()->getAuthIdentifier();
    }
}
