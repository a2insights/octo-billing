<?php

namespace Octo\Billing\Concerns;

trait ObserveQuota
{
    abstract public function subscriptionTarget();

    protected static function bootObserveQuota()
    {
        static::creating(function ($model) {
            $subscription = $model->subscriptionTarget();

            if (!$subscription) {
                return;
            }

            $features = $subscription->features;

            /** @var \Octo\Billing\Feature $featureObservable */
            $featureObservable = $features->filter(fn ($f) => $f->getModel() === self::class)->first();

            if ($featureObservable) {
                if ($subscription->getRemainingQuota($featureObservable) <= 0) {
                    abort(403, 'Subscription quota exceeded');
                }
            }
        });


        static::created(function ($model) {
             $subscription = $model->subscriptionTarget();

            if (!$subscription) {
                return;
            }

            $features = $subscription->features;

            /** @var \Octo\Billing\Feature $featureObservable */
            $featureObservable = $features->filter(fn ($f) => $f->getModel() === self::class)->first();

            if ($featureObservable) {
                $subscription->recordFeatureUsage($featureObservable->getId(), $featureObservable->calculeUsage($model));
            }
        });

        static::deleted(function ($model) {
             $subscription = $model->subscriptionTarget();

            if (!$subscription) {
                return;
            }

            $features = $subscription->features;

            /** @var \Octo\Billing\Feature $featureObservable */
            $featureObservable = $features->filter(fn ($f) => $f->getModel() === self::class)->first();

            if ($featureObservable) {
                $subscription->reduceFeatureUsage($featureObservable->getId(), $featureObservable->calculeUsage($model));
            }
        });
    }
}
