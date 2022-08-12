<?php

namespace OctoBilling\Models;

use Laravel\Cashier\Subscription as CashierSubscription;
use OctoBilling\Concerns\HasPlans;
use OctoBilling\Concerns\HasQuotas;
use OctoBilling\Saas;

class Subscription extends CashierSubscription
{
    use HasPlans;
    use HasQuotas;

    protected $appends = ['features'];

    public function getFeaturesAttribute()
    {
        return Saas::getPlan($this->stripe_price)->getFeatures();
    }

    /**
     * Get the service plan identifier for the resource.
     *
     * @return void
     */
    public function getPlanIdentifier()
    {
        return $this->stripe_price;
    }
}
