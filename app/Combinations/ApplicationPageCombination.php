<?php

namespace App\Combinations;

use App\Constants\AuthenticationConstant;
use App\Constants\PostConstant;
use App\Constants\ProfileConstant;
use App\Constants\ProjectConstant;

class ApplicationPageCombination
{
    /**
     * @param  array<string, mixed>|null  $userProfile
     * @return array<string, mixed>
     */
    public function page(string $pageName, ?array $userProfile): array
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
                'post_max_length' => PostConstant::BODY_MAX_LENGTH,
                'comment_max_length' => PostConstant::COMMENT_MAX_LENGTH,
            ],
            'pages' => [
                'login' => AuthenticationConstant::PAGE_LOGIN,
                'register' => AuthenticationConstant::PAGE_REGISTER,
                'dashboard' => AuthenticationConstant::PAGE_DASHBOARD,
                'profile' => ProfileConstant::PAGE_PROFILE,
            ],
            'page' => $pageName,
            'user' => $userProfile,
            'routes' => [
                'login' => route(AuthenticationConstant::ROUTE_LOGIN),
                'register' => route(AuthenticationConstant::ROUTE_REGISTER),
                'dashboard' => route(AuthenticationConstant::ROUTE_DASHBOARD),
                'logout' => route(AuthenticationConstant::ROUTE_LOGOUT),
                'profile' => route(ProfileConstant::ROUTE_PROFILE_PAGE),
                'profile_show' => route(ProfileConstant::ROUTE_PROFILE_SHOW),
                'profile_update' => route(ProfileConstant::ROUTE_PROFILE_UPDATE),
                'profile_image_update' => route(ProfileConstant::ROUTE_PROFILE_IMAGE_UPDATE),
                'feed' => route(PostConstant::ROUTE_FEED),
                'post_create' => route(PostConstant::ROUTE_POST_CREATE),
                'post_like_pattern' => PostConstant::URI_POST_LIKE,
                'post_comment_pattern' => PostConstant::URI_POST_COMMENT,
            ],
        ];
    }
}
