<?php

namespace Octo\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Octo\Billing\Saas;
use Octo\Billing\Tests\Models\User;
use Octo\Billing\Tests\TestCase;

class WebhookTest extends TestCase
{
    use WithoutMiddleware;

    public function test_webhook_for_invoice_payment_succeeded()
    {
        $user = User::factory()->create();

        $plan = Saas::getPlan(static::$billingFreePlanId);

        $subscription = $this->createStripeSubscription($user, $plan);

        $this->postJson(route('octo-billing.webhook'), [
            'id' => 'foo',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'customer' => $user->stripe_id,
                    'subscription' => $subscription->stripe_id,
                ],
            ],
        ])->assertOk();
    }
}
