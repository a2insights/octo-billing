<?php

namespace OctoBilling\Models;

use Illuminate\Database\Eloquent\Model;
use OctoBilling\Feature;
use OctoBilling\Saas;

class Usage extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'subscription_usages';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'subscription_id',
        'feature_id',
        'used',
        'used_total',
    ];

    /**
     * Recalculate the usage values based on the user-defined callbacks.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $subscription
     * @param  \OctoBilling\Feature  $feature
     * @return self
     */
    public function recalculate(Model $subscription, Feature $feature)
    {
        $usageValue = Saas::applyFeatureUsageSync($subscription, $feature);

        // If no callback was defined just return the same instance.
        if (is_null($usageValue)) {
            return $this;
        }

        return $this->fill([
            'used' => $usageValue,
            'used_total' => $usageValue,
        ]);
    }
}
