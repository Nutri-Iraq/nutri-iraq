<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\DailyTrackingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;

// ── Auth (بدون مصادقة)
Route::prefix('auth')->group(function () {
    Route::post('login',           [AuthController::class, 'login']);
});

// ── Protected routes (تحتاج token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::get('me',               [AuthController::class, 'me']);
        Route::put('change-password',  [AuthController::class, 'changePassword']);
    });

    // Members
    Route::apiResource('members', MemberController::class);
    Route::get('members/{member}/timeline', [MemberController::class, 'timeline']);

    // Measurements
    Route::get('members/{member}/measurements',       [MeasurementController::class, 'index']);
    Route::post('members/{member}/measurements',      [MeasurementController::class, 'store']);
    Route::get('members/{member}/measurements/chart', [MeasurementController::class, 'chart']);

    // Meal Plans
    Route::get('members/{member}/meal-plans',   [MealPlanController::class, 'index']);
    Route::post('members/{member}/meal-plans',  [MealPlanController::class, 'store']);
    Route::get('meal-plans/{mealPlan}',         [MealPlanController::class, 'show']);
    Route::put('meal-plans/{mealPlan}',         [MealPlanController::class, 'update']);
    Route::get('meal-plans/{mealPlan}/pdf',     [MealPlanController::class, 'pdf']);
    Route::post('meal-plans/{mealPlan}/send-whatsapp', [MealPlanController::class, 'sendWhatsapp']);

    // Daily Tracking
    Route::get('members/{member}/tracking',   [DailyTrackingController::class, 'index']);
    Route::post('members/{member}/tracking',  [DailyTrackingController::class, 'store']);
    Route::get('tracking/compliance-report',  [DailyTrackingController::class, 'complianceReport']);

    // Subscriptions
    Route::apiResource('subscriptions', SubscriptionController::class)->only(['index', 'store']);
    Route::put('subscriptions/{subscription}/renew',  [SubscriptionController::class, 'renew']);
    Route::put('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('subscriptions/expiring',              [SubscriptionController::class, 'expiring']);

    // Notifications
    Route::get('notifications',                        [NotificationController::class, 'index']);
    Route::put('notifications/{notification}/read',    [NotificationController::class, 'read']);
    Route::put('notifications/read-all',               [NotificationController::class, 'readAll']);
    Route::delete('notifications/{notification}',      [NotificationController::class, 'destroy']);
    Route::post('notifications/send-reminder',         [NotificationController::class, 'sendReminder']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('dashboard',          [ReportController::class, 'dashboard']);
        Route::get('weight-lost',        [ReportController::class, 'weightLost']);
        Route::get('by-governorate',     [ReportController::class, 'byGovernorate']);
        Route::get('bmi-distribution',   [ReportController::class, 'bmiDistribution']);
        Route::get('export',             [ReportController::class, 'export']);
    });

    // Users (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::put('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // Settings
    Route::get('settings',  [SettingsController::class, 'index']);
    Route::put('settings',  [SettingsController::class, 'update']);
});
