<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    // ==================== Subscribe ====================

    public function subscribe(User $user,Plan $plan,string $currency,string $billingCycle): Subscription {
        if ($this->hasActiveSubscription($user)) {
            throw new \Exception('User already has an active subscription.');
        }
        $planPrice = $plan->getPrice($currency, $billingCycle);
        if (! $planPrice) {
            throw new \Exception("No pricing found for {$currency} / {$billingCycle}.");
        }
        $periodEndsAt = $billingCycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();
        return DB::transaction(function () use ($user, $plan, $planPrice, $periodEndsAt) {
            $isTrialing  = $plan->hasTrial();
            $trialEndsAt = $isTrialing ? now()->addDays($plan->trial_days) : null;
            return Subscription::create([
                'user_id'                => $user->id,
                'plan_id'                => $plan->id,
                'plan_price_id'          => $planPrice->id,
                'status'                 => $isTrialing ? 'trialing' : 'active',
                'trial_ends_at'          => $trialEndsAt,
                'current_period_ends_at' => $periodEndsAt,
            ]);
        });
    }




    // ==================== Payment Handling ====================

    public function handleSuccessfulPayment(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {

            $billingCycle = $subscription->planPrice->billing_cycle;

            $newPeriodEnd = $billingCycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();
            $subscription->update([
                'status'                 => 'active',
                'grace_period_ends_at'   => null,
                'current_period_ends_at' => $newPeriodEnd,
            ]);
            Payment::create([
                'subscription_id' => $subscription->id,
                'amount'          => $subscription->planPrice->price,
                'currency'        => $subscription->planPrice->currency,
                'status'          => 'success',
            ]);
            return $subscription->fresh();
        });
    }

    public function handleFailedPayment(Subscription $subscription,string $reason = null): Subscription {
        return DB::transaction(function () use ($subscription, $reason) {

            $subscription->update([
                'status'               => 'past_due',
                'grace_period_ends_at' => now()->addDays(3),
            ]);

            Payment::create([
                'subscription_id' => $subscription->id,
                'amount'          => $subscription->planPrice->price,
                'currency'        => $subscription->planPrice->currency,
                'status'          => 'failed',
                'failure_reason'  => $reason,
            ]);
            return $subscription->fresh();
        });
    }

    // ==================== Cancel ====================

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status'      => 'canceled',
            'canceled_at' => now(),
        ]);

        return $subscription->fresh();
    }

    // ==================== Helpers ====================

    public function hasActiveSubscription(User $user): bool
    {
        return $user->subscriptions()
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->exists();
    }
}
