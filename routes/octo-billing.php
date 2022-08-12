<?php

use Illuminate\Support\Facades\Route;
use OctoBilling\Http\Controllers\BillingController;
use OctoBilling\Http\Controllers\InvoiceController;
use OctoBilling\Http\Controllers\PaymentMethodController;
use OctoBilling\Http\Controllers\BillingWebhook;
use OctoBilling\Http\Controllers\SubscriptionController;
use OctoBilling\Http\Middleware\Authorize;
use Octo\Common\Http\NotificationsController;
use Octo\System\Http\Controllers\DashboardController;
use Octo\System\Http\Controllers\SiteController;
use Octo\System\Http\Controllers\ThemesController;
use Octo\System\Http\Controllers\UsersController;

Route::post('billing/stripe/webhook', [BillingWebhook::class, 'handleWebhook'])->name('octo-billing.webhook');

Route::group(['middleware' => ['web']], function () {
    // We redirect filament login to jetstream login
    Route::redirect(config('filament.path') . '/login', '/login');

    Route::group(['middleware' => ['auth', 'verified']], function () {
        // Notifications
        Route::get('/user/notifications', [NotificationsController::class, 'index'])->name('notifications');

        // Billing
        Route::group([
            'prefix' => '/billing',
            'as' => 'billing.',
            'middleware' => [
                Authorize::class
            ],
        ], function () {
            Route::get('/', [BillingController::class, 'dashboard'])->name('dashboard');
            Route::get('/portal', [BillingController::class, 'portal'])->name('portal');

            Route::get('/subscription/subscribe/{plan}', [SubscriptionController::class, 'redirectWithSubscribeIntent'])->name('subscription.plan-subscribe');

            Route::resource('invoice', InvoiceController::class)->only('index');
            Route::resource('payment-method', PaymentMethodController::class)->only('index', 'create', 'store');
            Route::resource('subscription', SubscriptionController::class)->only('index');
        });

        // System
        Route::group(['middleware' => ['system.dashboard']], function () {
            Route::get('/system/users', [UsersController::class, 'index'])->name('system.users.index');
            Route::get('/system/site', [SiteController::class, 'index'])->name('system.site');
            Route::get('/system/themes', [ThemesController::class, 'index'])->name('system.themes');
            Route::prefix('/system/dashboard')->group(function () {
                Route::get('/', [DashboardController::class, 'index'])->name('system.dashboard');
            });
        });
    });
});
