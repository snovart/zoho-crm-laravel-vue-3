<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Zoho\ZohoCrmService;
use Illuminate\Console\Command;
use Throwable;

class ZohoPushAccount extends Command
{
    /**
     * Pass a local customer id.
     */
    protected $signature = 'zoho:push-account {customer_id}';

    protected $description = 'Create a Zoho Account from a local customer';

    /**
     * Handle the command.
     */
    public function handle(ZohoCrmService $zoho): int
    {
        $id = (int) $this->argument('customer_id');

        /** @var Customer|null $c */
        $c = Customer::find($id);
        if (!$c) {
            $this->error("Customer #{$id} not found.");
            return self::FAILURE;
        }

        // Build minimal payload expected by ZohoCrmService::createAccountFromCustomer
        $first = (string) ($c->first_name ?? '');
        $last  = (string) ($c->last_name ?? '');

        if ((!$first || !$last) && !empty($c->name)) {
            // naive split: "John Doe" -> ["John", "Doe"]
            $parts = preg_split('/\s+/', trim((string) $c->name), 2);
            $first = $first ?: ($parts[0] ?? '');
            $last  = $last  ?: ($parts[1] ?? '');
        }

        $payload = [
            'first_name' => $first,
            'last_name'  => $last,
            'email'      => (string) ($c->email ?? ''),
        ];

        try {
            $resp = $zoho->createAccountFromCustomer($payload);

            $this->info('Zoho Account response:');
            $this->line(json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Next step: we can store the Zoho Account ID locally (if we add a column)
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Zoho push failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
