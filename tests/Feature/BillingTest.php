<?php

namespace OctoBilling\Tests\Feature;

use OctoBilling\Tests\Models\User;
use OctoBilling\Tests\TestCase;

class BillingTest extends TestCase
{
    public function test_billing_redirect_to_portal()
    {
        $this->markTestSkipped('This test is not implemented yet.');

        $user = User::factory()->create();

        $user->subscriptions()->delete();

        $this->actingAs($user)
             ->get(route('billing.subscription.plan-subscribe', ['plan' => static::$billingFreePlanId]))
             ->assertOk();

        $this->actingAs($user)
             ->get(route('billing.portal'))
             ->assertStatus(302);
    }
}
