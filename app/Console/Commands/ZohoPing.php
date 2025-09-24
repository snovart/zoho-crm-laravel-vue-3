<?php

namespace App\Console\Commands;

use App\Services\Zoho\ZohoCrmService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Simple ping to verify Zoho CRM connectivity.
 */
class ZohoPing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoho:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Zoho CRM connectivity (prints organization info)';

    /**
     * Execute the console command.
     */
    public function handle(ZohoCrmService $client): int
    {
        try {
            // Ping just for test is everything ok with auth
            $data = $client->get(config('zoho.endpoints.accounts'), ['per_page' => 1, 'fields' => 'id']);

            $this->info('Zoho CRM connectivity OK ✅');
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Zoho ping failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
