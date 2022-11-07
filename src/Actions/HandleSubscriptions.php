<?php

namespace Octo\Billing\Actions;

use Octo\Billing\Billing;
use Octo\Billing\Contracts\HandleSubscriptions as HandleSubscriptionsContract;
use Octo\Billing\Plan;

class HandleSubscriptions implements HandleSubscriptionsContract
{
    /**
     * Mutate the checkout object before redirecting the user to subscribe to a certain plan.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Octo\Billing\Plan  $plan
     * @return mixed
     */
    public function checkoutOnSubscription($subscription, $billable, Plan $plan)
    {
        return $subscription->checkout([
            'success_url' => config('octo-billing.subscription_index'),
            'cancel_url' => config('octo-billing.subscription_index'),
        ]);
    }

    /**
     * Subscribe the user to a given plan.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Octo\Billing\Plan  $plan
     * @return void
     */
    public function subscribeToPlan($billable, Plan $plan)
    {
        $subscription = $billable->newSubscription($plan->getName(), $plan->getId());

        $meteredFeatures = $plan->getMeteredFeatures();

        if (! $meteredFeatures->isEmpty()) {
            foreach ($meteredFeatures as $feature) {
                $subscription->meteredPrice($feature->getMeteredId());
            }
        }

        $subscription = $subscription->create($billable->defaultPaymentMethod()->id);

        $subscription->stripe_price = $plan->getId();

        $subscription->save();
    }

    /**
     * Swap the current subscription plan.
     *
     * @param  \Octo\Billing\Models\Subscription  $subscription
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \Octo\Billing\Plan  $plan
     * @return void
     */
    public function swapToPlan($subscription, $billable, Plan $plan)
    {
        if (Billing::proratesOnSwap()) {
            $subscription->swap($plan->getId());
        } else {
            $subscription->noProrate()->swap($plan->getId());
        }
    }

    /**
     * Define the logic to be called when the user requests resuming a subscription.
     *
     * @param  \Octo\Billing\Models\Subscription  $subscription
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @return void
     */
    public function resumeSubscription($subscription, $billable)
    {
        $subscription->resume();
    }

    /**
     * Define the subscriptioncancellation action.
     *
     * @param  \Octo\Billing\Models\Subscription  $subscription
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @return void
     */
    public function cancelSubscription($subscription, $billable)
    {
        $subscription->cancel();
    }
}
