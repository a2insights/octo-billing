<?php

use Illuminate\Support\Facades\Route;
use Octo\Billing\Http\Controllers\BillingWebhook;

Route::post('octo-billing/stripe/webhook', [BillingWebhook::class, 'handleWebhook'])->name('octo-billing.webhook');
