<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Services\ManagerAssignmentService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Mass-assign managers to deals that have NULL manager_id, using the
 * same business rules as the runtime observer/service.
 *
 * Options:
 *  --chunk[=100]         Process deals in chunks (default: 100)
 *  --dry                 Dry-run (do not persist any changes)
 *  --source1-email=...   Override for Source 1 fixed manager email
 *  --source2-pool=...    CSV override for Source 2 pool (e.g. "a@x.com,b@y.com")
 *  --default-pool=...    CSV override for default pool (Source 3/4/5)
 */
class AssignManagersForDeals extends Command
{
    /** @var string */
    protected $signature = 'deals:assign-managers
        {--chunk=100 : Number of rows to process per chunk}
        {--dry : Dry-run, do not write changes}
        {--source1-email= : Override fixed manager email for Source 1}
        {--source2-pool= : Override CSV for Source 2 pool}
        {--default-pool= : Override CSV for default pool (Source 3/4/5)}';

    /** @var string */
    protected $description = 'Assign managers to deals with NULL manager_id according to rules';

    /**
     * Execute the console command.
     *
     * @param ManagerAssignmentService $service
     * @return int
     */
    public function handle(ManagerAssignmentService $service): int
    {
        $chunk   = (int) $this->option('chunk');
        $isDry   = (bool) $this->option('dry');

        // Build per-run overrides from CLI options
        $override = $this->buildOverrides();

        $this->info(sprintf(
            'Starting backfill (chunk=%d, dry=%s)...',
            $chunk,
            $isDry ? 'yes' : 'no'
        ));

        $total = 0;
        $assigned = 0;

        Deal::whereNull('manager_id')
            ->orderBy('id')
            ->chunkById($chunk, function ($deals) use ($service, $override, $isDry, &$total, &$assigned) {
                foreach ($deals as $deal) {
                    $total++;

                    if ($isDry) {
                        // Simulate assignment to show what would happen
                        try {
                            $simulated = (clone $deal); // shallow clone for display only
                            // We won't call the service to avoid DB writes in dry-run
                            $this->line("[dry] Deal #{$deal->id} would be assigned based on source '{$deal->source}'");
                        } catch (Throwable $e) {
                            $this->warn("[dry] Deal #{$deal->id} simulation failed: {$e->getMessage()}");
                        }
                        continue;
                    }

                    try {
                        $service->assignForDeal($deal, $override);
                        $assigned++;
                        $this->line("Assigned deal #{$deal->id} -> manager_id={$deal->manager_id}");
                    } catch (Throwable $e) {
                        $this->error("Failed to assign deal #{$deal->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Backfill completed. Scanned: {$total}, newly assigned: {$assigned}.");

        return self::SUCCESS;
    }

    /**
     * Build override array from CLI options.
     *
     * @return array{
     *   source1_email?: string,
     *   source2_pool_emails?: string[],
     *   default_pool_emails?: string[],
     * }
     */
    private function buildOverrides(): array
    {
        $override = [];

        if ($s1 = $this->option('source1-email')) {
            $override['source1_email'] = trim((string) $s1);
        }

        if ($s2 = $this->option('source2-pool')) {
            $override['source2_pool_emails'] = $this->csvToArray((string) $s2);
        }

        if ($def = $this->option('default-pool')) {
            $override['default_pool_emails'] = $this->csvToArray((string) $def);
        }

        return $override;
    }

    /**
     * Convert CSV string to trimmed, non-empty array.
     *
     * @param string $csv
     * @return string[]
     */
    private function csvToArray(string $csv): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $csv)), static fn($v) => $v !== ''));
    }
}
