<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Services\Zoho\ZohoCrmService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Push a single local deal to Zoho CRM.
 * Expects manager assignment to already exist in our DB.
 */
class ZohoPushDeal extends Command
{
    /**
     * Usage: php artisan zoho:push-deal {deal_id}
     */
    protected $signature = 'zoho:push-deal {deal_id}';

    protected $description = 'Push one deal to Zoho CRM (creates Account if needed, then Deal with manager email).';

    public function handle(ZohoCrmService $zoho): int
    {
        $dealId = (int) $this->argument('deal_id');

        /** @var Deal|null $deal */
        $deal = Deal::with(['customer', 'manager'])->find($dealId);
        if (!$deal) {
            $this->error("Deal #{$dealId} not found.");
            return self::FAILURE;
        }

        // Must have a related customer
        if (!$deal->customer) {
            $this->error("Deal #{$deal->id} has no related customer.");
            return self::FAILURE;
        }

        // Must have a manager (email will be sent to Zoho custom field)
        if (!$deal->manager) {
            $this->error("Deal #{$deal->id} has no assigned manager.");
            return self::FAILURE;
        }

        try {
            $this->line("Pushing Deal #{$deal->id} ...");

            // Delegate to service: creates Account (if needed) and Deal, sets manager email
            $resp = $zoho->pushDeal($deal);

            $this->info('Zoho response:');
            $this->line(json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Zoho push failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
