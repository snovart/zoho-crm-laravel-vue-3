<?php

namespace App\Observers;

use App\Models\Deal;
use App\Services\ManagerAssignmentService;

class DealObserver
{
    /**
     * Handle the Deal "created" event.
     *
     * @param Deal $deal Newly created deal instance
     * @return void
     */
    public function created(Deal $deal): void
    {
        // Auto-assign a manager right after the deal is persisted
        app(ManagerAssignmentService::class)->assignForDeal($deal);
    }
}
