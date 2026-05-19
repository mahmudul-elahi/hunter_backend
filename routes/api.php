<?php

use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminPredictionController;
use App\Http\Controllers\Api\Admin\AdminPromoCodeController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminSubscriptionPlanController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Auth\UserAuthController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\OnboardingController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\SupportController;
use App\Http\Controllers\Api\User\UserPredictionController;
use Illuminate\Support\Facades\Route;

Route::post('webhook/stripe', [StripeWebhookController::class, 'handleWebhook']);

Route::prefix('auth')->group(function () {
    Route::post('login', [UserAuthController::class, 'login']);
    Route::post('verify-email-otp', [UserAuthController::class, 'verifyEmailOtp']);
    Route::post('resend-verification-otp', [UserAuthController::class, 'resendVerificationOtp']);
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('forgot-password', [UserAuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [UserAuthController::class, 'verifyOtp']);
    Route::post('reset-password', [UserAuthController::class, 'resetPassword']);
    Route::post('google', [SocialAuthController::class, 'googleLogin']);
    Route::post('apple', [SocialAuthController::class, 'appleLogin']);
});

Route::post('admin/login', [AdminAuthController::class, 'login']);

Route::middleware(['auth:api', 'role:user'])->group(function () {
    Route::post('auth/logout', [UserAuthController::class, 'logout']);
    Route::post('auth/refresh', [UserAuthController::class, 'refresh']);

    Route::get('me', [ProfileController::class, 'show']);

    Route::post('user/onboarding/categories', [OnboardingController::class, 'saveCategories']);

    Route::get('user/profile', [ProfileController::class, 'show']);
    Route::put('user/profile', [ProfileController::class, 'update']);
    Route::put('user/change-password', [ProfileController::class, 'changePassword']);
    Route::post('user/profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('user/profile/avatar', [ProfileController::class, 'deleteAvatar']);

    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('subscriptions/my-subscription', [SubscriptionController::class, 'mySubscription']);
    Route::post('subscriptions/start-trial', [SubscriptionController::class, 'startTrial']);
    Route::get('subscriptions/validate-promo', [SubscriptionController::class, 'validatePromo']);
    Route::post('subscriptions/apply-promo', [SubscriptionController::class, 'applyPromo']);
    Route::delete('subscriptions/cancel', [SubscriptionController::class, 'cancel']);

    Route::get('predictions', [UserPredictionController::class, 'index']);
    Route::get('predictions/{id}', [UserPredictionController::class, 'show']);
    Route::get('categories', [UserPredictionController::class, 'categories']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::post('support/contact', [SupportController::class, 'contact']);
});

Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::post('refresh', [AdminAuthController::class, 'refresh']);

    Route::get('dashboard/overview', [AdminDashboardController::class, 'overview']);
    Route::get('dashboard/win-rate-chart', [AdminDashboardController::class, 'winRateChart']);
    Route::get('dashboard/recent-predictions', [AdminDashboardController::class, 'recentPredictions']);

    Route::get('predictions/overview', [AdminPredictionController::class, 'overview']);
    Route::get('predictions', [AdminPredictionController::class, 'index']);
    Route::post('predictions', [AdminPredictionController::class, 'store']);
    Route::get('predictions/{id}', [AdminPredictionController::class, 'show']);
    Route::put('predictions/{id}', [AdminPredictionController::class, 'update']);
    Route::delete('predictions/{id}', [AdminPredictionController::class, 'destroy']);
    Route::patch('predictions/{id}/status', [AdminPredictionController::class, 'updateStatus']);

    Route::get('subscriptions/overview', [AdminSubscriptionPlanController::class, 'overview']);
    Route::get('subscriptions/plans', [AdminSubscriptionPlanController::class, 'index']);
    Route::post('subscriptions/plans', [AdminSubscriptionPlanController::class, 'store']);
    Route::put('subscriptions/plans/{id}', [AdminSubscriptionPlanController::class, 'update']);
    Route::delete('subscriptions/plans/{id}', [AdminSubscriptionPlanController::class, 'destroy']);

    Route::get('promo-codes', [AdminPromoCodeController::class, 'index']);
    Route::post('promo-codes', [AdminPromoCodeController::class, 'store']);
    Route::put('promo-codes/{id}', [AdminPromoCodeController::class, 'update']);
    Route::delete('promo-codes/{id}', [AdminPromoCodeController::class, 'destroy']);
    Route::patch('promo-codes/{id}/toggle', [AdminPromoCodeController::class, 'toggle']);

    Route::get('users/overview', [AdminUserController::class, 'overview']);
    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::patch('users/{id}/status', [AdminUserController::class, 'toggleStatus']);

    Route::get('categories', [AdminCategoryController::class, 'index']);
    Route::post('categories', [AdminCategoryController::class, 'store']);
    Route::put('categories/{id}', [AdminCategoryController::class, 'update']);

    Route::get('settings/profile', [AdminSettingsController::class, 'profile']);
    Route::put('settings/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('settings/password', [AdminSettingsController::class, 'changePassword']);
    Route::get('settings/notifications', [AdminSettingsController::class, 'notificationSettings']);
    Route::put('settings/notifications', [AdminSettingsController::class, 'updateNotificationSettings']);
});
