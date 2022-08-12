<?php

namespace OctoBilling\Tests;

use Illuminate\Http\Request;
use Laravel\Cashier\Cashier as StripeCashier;
use OctoBilling\OctoBillingServiceProvider;
use OctoBilling\Billing;
use OctoBilling\Saas;
use Stripe\ApiResource;
use Stripe\Exception\InvalidRequestException;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Price;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected static $productId;

    protected static $freeProductId;

    protected static $billingMonthlyPlanId;

    protected static $billingMeteredPriceId;

    protected static $billingYearlyPlanId;

    protected static $billingFreePlanId;

    protected static $billingPlanId;

    /**
     * Reset the database.
     *
     * @return void
     */
    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        Saas::clearPlans();

        Saas::cleanSyncUsageCallbacks();

        $this->resetDatabase();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $freeStripePlan = Saas::plan('Free Plan', static::$billingFreePlanId, static::$billingYearlyPlanId, static::$billingPlanId)
            ->features([
                Saas::feature('Build Minutes', 'build.minutes', 10),
                Saas::feature('Seats', 'teams', 5)->notResettable(),
            ]);

        Saas::plan('Monthly $10', static::$billingMonthlyPlanId)
            ->inheritFeaturesFromPlan($freeStripePlan, [
                Saas::feature('Build Minutes', 'build.minutes', 3000),
                Saas::meteredFeature('Metered Build Minutes', 'metered.build.minutes', 3000)
                    ->meteredPrice(static::$billingMeteredPriceId, 0.1, 'minute'),
                Saas::feature('Seats', 'teams', 10)->notResettable(),
                Saas::feature('Mails', 'mails', 300),
            ]);

        Saas::plan('Yearly $100', static::$billingYearlyPlanId)
            ->inheritFeaturesFromPlan($freeStripePlan, [
                Saas::feature('Build Minutes', 'build.minutes')->unlimited(),
                Saas::feature('Seats', 'teams', 10)->notResettable(),
            ]);

        Saas::plan('Yearly $100', static::$billingPlanId)
            ->inheritFeaturesFromPlan($freeStripePlan, [
                Saas::feature('Build Minutes', 'build.minutes')->unlimited(),
                Saas::feature('Seats', 'teams', 1770)->notResettable(),
            ]);

        Billing::resolveBillable(function (Request $request) {
            return $request->user();
        });

        StripeCashier::useCustomerModel(Models\User::class);

        Billing::resolveAuthorization(function ($billable, Request $request) {
            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (file_exists(__DIR__ . '/../.env')) {
            \Dotenv\Dotenv::createImmutable(__DIR__ . '../..')->load();
        }

        Stripe::setApiKey(getenv('STRIPE_SECRET') ?: env('STRIPE_SECRET'));

        static::$freeProductId = Product::create([
            'name' => 'Laravel Cashier Test Free Product',
            'type' => 'service',
        ])->id;

        static::$productId = Product::create([
            'name' => 'Laravel Cashier Test Product',
            'type' => 'service',
        ])->id;

        static::$billingFreePlanId = Plan::create([
            'nickname' => 'Free',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 0,
            'product' => static::$freeProductId,
        ])->id;

        static::$billingMonthlyPlanId = Plan::create([
            'nickname' => 'Monthly $10',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 1000,
            'product' => static::$productId,
        ])->id;

        static::$billingYearlyPlanId = Plan::create([
            'nickname' => 'Yearly $100',
            'currency' => 'USD',
            'interval' => 'year',
            'billing_scheme' => 'per_unit',
            'amount' => 10000,
            'product' => static::$productId,
        ])->id;

        static::$billingPlanId = Plan::create([
            'nickname' => 'Plan',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 1200,
            'product' => static::$productId,
        ])->id;

        static::$billingFreePlanId = Plan::create([
            'nickname' => 'Free',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 0,
            'product' => static::$productId,
        ])->id;

        static::$billingMeteredPriceId = Price::create([
            'nickname' => 'Monthly Metered $0.01 per unit',
            'currency' => 'USD',
            'recurring' => [
                'interval' => 'month',
                'usage_type' => 'metered',
            ],
            'unit_amount' => 1,
            'product' => static::$productId,
        ])->id;
    }

    /**
    * {@inheritdoc}
    */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        static::deleteStripeResource(new Plan(static::$billingMonthlyPlanId));
        static::deleteStripeResource(new Plan(static::$billingYearlyPlanId));
        static::deleteStripeResource(new Plan(static::$billingFreePlanId));
        static::deleteStripeResource(new Plan(static::$billingPlanId));
        static::deleteStripeResource(new Product(static::$productId));
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \Laravel\Cashier\CashierServiceProvider::class,
            OctoBillingServiceProvider::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $config = require __DIR__ . './../config/octo.php';

        $app['config']->set('octo', $config);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix'   => '',
        ]);

        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');
        $app['config']->set('auth.providers.users.model', \OctoBilling\Tests\Models\User::class);

        $app['config']->set('billing.middleware', [
            'web',
            \OctoBilling\Http\Middleware\Authorize::class,
        ]);

        $app['config']->set('cashier.webhook.secret', null);

        $app['config']->set('jetstream.stack', 'livewire');
    }

    /**
     * Reset the database.
     *
     * @return void
     */
    /*    protected function resetDatabase()
       {
           file_put_contents(__DIR__.'/database.sqlite', null);
       } */

    /**
     * Create a new subscription.
     *
     * @param  \OctoBilling\Test\Models\Stripe\User  $user
     * @param  \OctoBilling\Plan  $plan
     * @return \OctoBilling\Models\Subscription
     */
    protected function createStripeSubscription($user, $plan)
    {
        $subscription = $user->newSubscription('main', $plan->getId());
        $meteredFeatures = $plan->getMeteredFeatures();

        if (! $meteredFeatures->isEmpty()) {
            foreach ($meteredFeatures as $feature) {
                $subscription->meteredPrice($feature->getMeteredId());
            }
        }

        return $subscription->create('pm_card_visa');
    }

    protected static function deleteStripeResource(ApiResource $resource)
    {
        try {
            $resource->delete();
        } catch (InvalidRequestException $e) {
            //
        }
    }
}

namespace App\View\Components;

use Illuminate\View\Component;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return <<<'blade'
            <div>
                {{ $slot }}
            </div>
        blade;
    }
}
