<?php

use Illuminate\Support\Facades\Route;
use OctoBilling\Http\Controllers\BillingWebhook;

Route::post('billing/stripe/webhook', [BillingWebhook::class, 'handleWebhook'])->name('octo-billing.webhook');
