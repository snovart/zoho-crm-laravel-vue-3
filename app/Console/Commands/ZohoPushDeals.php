<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Services\Zoho\ZohoCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mass-push deals to Zoho CRM (creates Account if needed, then Deal).
 * Manager assignment is expected to already exist in our DB.
 */
class ZohoPushDeals extends Command
{
    /**
     * Usage:
     *   php artisan zoho:push-deals --ids=1,2,3
     *   php artisan zoho:push-deals --all
     *   php artisan zoho:push-deals --all --chunk=20 --delay=250 --pause=1500
     */
    protected $signature = 'zoho:push-deals 
                            {--ids= : Comma-separated deal IDs to push} 
                            {--all : Push all deals from DB}
                            {--chunk=20 : Number of deals per batch}
                            {--delay=250 : Delay between deals in milliseconds}
                            {--pause=1500 : Pause between batches in milliseconds}';

    protected $description = 'Mass push deals to Zoho CRM (creates Account if needed, then Deal).';

    public function handle(ZohoCrmService $zoho): int
    {
        $idsOption   = $this->option('ids');
        $allOption   = $this->option('all');
        $chunkSize   = max(1, (int) $this->option('chunk'));
        $delayMs     = max(0, (int) $this->option('delay'));
        $pauseMs     = max(0, (int) $this->option('pause'));

        // Collect deals based on input options
        if ($idsOption) {
            $ids   = array_filter(array_map('intval', explode(',', $idsOption)));
            $deals = Deal::with(['customer', 'manager'])->whereIn('id', $ids)->get();
        } elseif ($allOption) {
            $deals = Deal::with(['customer', 'manager'])->get();
        } else {
            $this->error('Please provide --ids or --all option.');
            return self::FAILURE;
        }

        if ($deals->isEmpty()) {
            $this->info('No deals found for pushing.');
            return self::SUCCESS;
        }

        $this->info('Found ' . $deals->count() . " deal(s) to push. Chunk size: {$chunkSize}");

        $successCount = 0;
        $failedCount  = 0;
        $batchIndex   = 0;

        foreach ($deals->chunk($chunkSize) as $batch) {
            $batchIndex++;
            $this->line("--- Processing batch #{$batchIndex} ({$batch->count()} deal(s)) ---");

            foreach ($batch as $deal) {
                try {
                    // Skip if no customer
                    if (!$deal->customer) {
                        $this->warn("Skip Deal #{$deal->id}: no customer.");
                        continue;
                    }
                    // Skip if no manager
                    if (!$deal->manager) {
                        $this->warn("Skip Deal #{$deal->id}: no manager.");
                        continue;
                    }

                    $this->line("Pushing Deal #{$deal->id} ...");
                    $this->pushWithSingleRetry($zoho, $deal);

                    $this->info("✔ Deal #{$deal->id} pushed successfully");
                    $successCount++;
                } catch (Throwable $e) {
                    $this->error("✖ Failed to push Deal #{$deal->id}: " . $e->getMessage());
                    $failedCount++;
                }

                // Throttle between deals
                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            // Pause between batches
            if ($pauseMs > 0) {
                $this->line("… pausing {$pauseMs} ms before next batch");
                usleep($pauseMs * 1000);
            }
        }

        $this->info("Finished. Success: {$successCount}, Failed: {$failedCount}");

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Push one deal with a single retry on rate-limit-like errors.
     */
    private function pushWithSingleRetry(ZohoCrmService $zoho, Deal $deal): void
    {
        try {
            $zoho->pushDeal($deal);
        } catch (Throwable $e) {
            $msg = (string) $e->getMessage();

            // Detect typical rate-limit / too-many-requests messages
            $looksRateLimited =
                Str::contains(Str::lower($msg), [
                    'too many requests',
                    'please try again after some time',
                    'rate limit',
                    '429',
                ]);

            if ($looksRateLimited) {
                // Back off briefly and retry once
                usleep(1200 * 1000); // 1.2s
                $zoho->pushDeal($deal);
                return;
            }

            // Re-throw non-rate-limit errors
            throw $e;
        }
    }
}
