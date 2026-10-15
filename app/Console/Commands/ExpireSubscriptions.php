<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:expire')]
#[Description('Flip active subscriptions past their ends_at to expired')]
class ExpireSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $count = $subscriptionService->expireOverdue();

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
