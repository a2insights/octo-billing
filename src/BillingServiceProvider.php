<?php

namespace Octo\Billing;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Octo\Billing\Actions\HandleSubscriptions;

class BillingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/octo-billing.php' => config_path('octo-billing.php'),
        ], 'octo-billing-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/octo-billing.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'octo-billing');

        Cashier::useSubscriptionModel(\Octo\Billing\Models\Subscription::class);

        if (config('octo-billing.dont_prorate_on_swap', true)) {
            Billing::dontProrateOnSwap();
        }

        Billing::handleSubscriptionsUsing(HandleSubscriptions::class);

        Saas::currency(config('octo-billing.currency', 'BRL'));
    }
}
