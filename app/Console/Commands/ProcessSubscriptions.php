<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptions extends Command
{
    protected $signature   = 'subscriptions:process';
    protected $description = 'Process expired trials and grace periods daily.';

    public function handle(SubscriptionService $service): void
    {
        $this->processExpiredTrials();
        $this->processExpiredGracePeriods($service);

        $this->info('Subscriptions processed successfully.');
    }

    private function processExpiredTrials(): void
    {
        $count = Subscription::where('status', 'trialing')
            ->where('trial_ends_at', '<=', now())
            ->update([
                'status'        => 'active',
                'trial_ends_at' => null,
            ]);

        $this->info("Trials expired → Active: {$count}");
    }

    private function processExpiredGracePeriods(SubscriptionService $service): void
    {
        $subscriptions = Subscription::where('status', 'past_due')
            ->where('grace_period_ends_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $service->cancel($subscription);
        }

        $this->info("Grace periods expired → Canceled: {$subscriptions->count()}");
    }
}
