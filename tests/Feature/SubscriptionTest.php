<?php

namespace OctoBilling\Tests\Feature;

use OctoBilling\Saas;
use OctoBilling\Tests\Models\User;
use OctoBilling\Tests\TestCase;

class SubscriptionTest extends TestCase
{
    public function test_index_subscriptions()
    {
        $this->markTestSkipped('This test is not implemented yet.');

        $user = User::factory()->create();
        $plan = Saas::getPlan(static::$billingFreePlanId);
        $subscription = $this->createStripeSubscription($user, $plan);

        $this->actingAs($user)
            ->get(route('octo-billing.subscription.index'))
            ->assertOk()
            ->assertSee($subscription->getPlan()->getName());
    }

    public function test_subscribe_to_free_plan()
    {
        $this->markTestSkipped('This test is not implemented yet.');

        $user = User::factory()->create();

        $user->subscriptions()->delete();

        $this->actingAs($user)
            ->get(route('octo-billing.subscription.plan-subscribe', ['plan' => static::$billingFreePlanId]))
            ->assertOk();

        $user->newSubscription('main', static::$billingFreePlanId)->create('pm_card_us');

        $this->assertCount(1, $user->subscriptions);
    }

    public function test_subscribe_to_paid_plan_without_payment_method()
    {
        $this->markTestSkipped('This test is not implemented yet.');

        $user = User::factory()->create();

        $user->subscriptions()->delete();

        $this->actingAs($user)
            ->get(route('octo-billing.subscription.plan-subscribe', ['plan' => static::$billingPlanId]))
            ->assertOk();

        $this->assertCount(0, $user->subscriptions);
    }

    public function test_swap_to_paid_plan_without_payment_method()
    {
        $this->markTestSkipped('This test is not implemented yet.');

        $user = User::factory()->create();

        $user->subscriptions()->delete();

        $this->actingAs($user)
            ->get(route('octo-billing.subscription.plan-subscribe', ['plan' => static::$billingFreePlanId]))
            ->assertOk();

        $user->newSubscription('main', static::$billingFreePlanId)->create('pm_card_us');

        $user->deletePaymentMethods();
    }

    public function test_cancel_and_resume_plan()
    {
        $this->markTestSkipped('This test is not implemented yet.');

        $user = User::factory()->create();

        $user->subscriptions()->delete();

        $this->actingAs($user)
            ->get(route('octo-billing.subscription.plan-subscribe', ['plan' => static::$billingFreePlanId]))
            ->assertOk();

        $user->newSubscription('main', static::$billingFreePlanId)->create('pm_card_us');
    }
}
