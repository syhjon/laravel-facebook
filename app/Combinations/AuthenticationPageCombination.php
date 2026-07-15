<?php

namespace App\Combinations;

use App\Constants\AuthenticationConstant;
use App\Constants\ProjectConstant;

class AuthenticationPageCombination
{
    /**
     * @param  array<string, mixed>|null  $user
     * @return array{project: array{name: string, technology_label: string, theme: array<string, string>}, constraints: array<string, int>, page: string, user: array<string, mixed>|null, routes: array<string, string>}
     */
    public function page(string $page, ?array $user): array
    {
        return [
            'project' => [
                'name' => ProjectConstant::NAME,
                'technology_label' => ProjectConstant::TECHNOLOGY_LABEL,
                'theme' => [
                    'primary' => ProjectConstant::PRIMARY_COLOR,
                    'primary_rgb' => ProjectConstant::PRIMARY_RGB,
                    'primary_hover' => ProjectConstant::PRIMARY_HOVER_COLOR,
                    'primary_active' => ProjectConstant::PRIMARY_ACTIVE_COLOR,
                    'primary_text' => ProjectConstant::PRIMARY_TEXT_COLOR,
                    'primary_subtle' => ProjectConstant::PRIMARY_SUBTLE_COLOR,
                ],
            ],
            'constraints' => [
                'name_max_length' => AuthenticationConstant::NAME_MAX_LENGTH,
                'email_max_length' => AuthenticationConstant::EMAIL_MAX_LENGTH,
                'password_min_length' => AuthenticationConstant::PASSWORD_MIN_LENGTH,
            ],
            'page' => $page,
            'user' => $user,
            'routes' => [
                'login' => route(AuthenticationConstant::ROUTE_LOGIN),
                'register' => route(AuthenticationConstant::ROUTE_REGISTER),
                'dashboard' => route(AuthenticationConstant::ROUTE_DASHBOARD),
                'logout' => route(AuthenticationConstant::ROUTE_LOGOUT),
            ],
        ];
    }
}
