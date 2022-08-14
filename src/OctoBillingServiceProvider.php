<?php

namespace OctoBilling;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use OctoBilling\OctoBilling;
use OctoBilling\Saas;

class OctoBillingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/octo-billing.php' => config_path('octo-billing.php'),
        ], 'octo-billing-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/octo-billing.php');

        Cashier::useSubscriptionModel(\OctoBilling\Models\Subscription::class);

        if (config('octo-billing.dont_prorate_on_swap', true)) {
            OctoBilling::dontProrateOnSwap();
        }

        OctoBilling::handleSubscriptionsUsing(HandleSubscriptions::class);

        Saas::currency(config('octo-billing.currency', 'BRL'));
    }
}
