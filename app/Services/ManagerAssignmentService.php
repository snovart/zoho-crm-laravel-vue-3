<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManagerAssignmentService
{
    /**
     * Assign a manager to the given deal according to business rules.
     *
     * @param Deal  $deal     Deal instance that needs a manager
     * @param array $override Optional override configuration:
     *                        [
     *                          'source1_email'       => string,
     *                          'source2_pool_emails' => string[],
     *                          'default_pool_emails' => string[],
     *                        ]
     *
     * @return Deal The deal with an assigned manager_id set and persisted
     *
     * @throws RuntimeException If no manager is available
     */
    public function assignForDeal(Deal $deal, array $override = []): Deal
    {
        if ($deal->manager_id) {
            return $deal; // already assigned
        }

        $cfg = array_replace_recursive(
            config('manager_assignment', []),
            $override
        );

        return DB::transaction(function () use ($deal, $cfg) {
            $pool = $this->getPoolBySource((string) $deal->source, $cfg);

            /** @var Manager|null $manager */
            $manager = Manager::whereIn('id', $pool->pluck('id'))
                ->orderBy('deals_count', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$manager) {
                throw new RuntimeException('No available manager for assignment.');
            }

            $deal->manager_id = $manager->id;
            $deal->save();

            $manager->increment('deals_count');

            return $deal;
        });
    }

    /**
     * Build an eligible manager pool for a given source.
     *
     * @param string $source The "source" field value of the deal
     * @param array  $cfg    Assignment configuration (see assignForDeal for structure)
     *
     * @return Collection<Manager> List of eligible managers
     */

    /**
     * Build an eligible manager pool for a given source.
     *
     * @param  string $source
     * @param  array  $cfg   (ignored for hard rules; left for future extensions)
     * @return \Illuminate\Database\Eloquent\Collection<\App\Models\Manager>
     */
    protected function getPoolBySource(string $source, array $cfg): \Illuminate\Database\Eloquent\Collection
    {
        $source = trim($source);

        // Hard business rules per spec:
        // Source 1  -> manager id = 4 (single choice)
        if ($source === 'Source 1') {
            return Manager::query()->where('id', 4)->get();
        }

        // Source 2  -> among managers 1 and 2 (choose by lowest deals_count)
        if ($source === 'Source 2') {
            return Manager::query()->whereIn('id', [1, 2])->get();
        }

        // Source 3/4/5 -> among all 1..5 (choose by lowest deals_count)
        if (in_array($source, ['Source 3', 'Source 4', 'Source 5'], true)) {
            return Manager::query()->whereIn('id', [1, 2, 3, 4, 5])->get();
        }

        // Fallback: same as 3/4/5
        return Manager::query()->whereIn('id', [1, 2, 3, 4, 5])->get();
    }

}
