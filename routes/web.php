<?php

use App\Constants\AuthenticationConstant;
use App\Constants\PostConstant;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route(AuthenticationConstant::ROUTE_DASHBOARD)
        : redirect()->route(AuthenticationConstant::ROUTE_LOGIN);
});

Route::middleware('guest')->group(function () {
    Route::get(AuthenticationConstant::URI_LOGIN, [AuthController::class, 'showLogin'])
        ->name(AuthenticationConstant::ROUTE_LOGIN);
    Route::post(AuthenticationConstant::URI_LOGIN, [AuthController::class, 'login']);
    Route::get(AuthenticationConstant::URI_REGISTER, [AuthController::class, 'showRegister'])
        ->name(AuthenticationConstant::ROUTE_REGISTER);
    Route::post(AuthenticationConstant::URI_REGISTER, [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get(AuthenticationConstant::URI_DASHBOARD, [AuthController::class, 'dashboard'])
        ->name(AuthenticationConstant::ROUTE_DASHBOARD);
    Route::post(AuthenticationConstant::URI_LOGOUT, [AuthController::class, 'logout'])
        ->name(AuthenticationConstant::ROUTE_LOGOUT);
    Route::get(PostConstant::URI_FEED, [FeedController::class, 'index'])
        ->name(PostConstant::ROUTE_FEED);
    Route::post(PostConstant::URI_POSTS, [FeedController::class, 'store'])
        ->name(PostConstant::ROUTE_POST_CREATE);
    Route::post(PostConstant::URI_POST_LIKE, [FeedController::class, 'toggleLike'])
        ->name(PostConstant::ROUTE_POST_LIKE);
    Route::post(PostConstant::URI_POST_COMMENT, [FeedController::class, 'storeComment'])
        ->name(PostConstant::ROUTE_POST_COMMENT);
});
