<?php

namespace App\Services\Zoho;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZohoCrmService
{
    private ZohoAuthService $auth;
    private string $apiBase;

    public function __construct(ZohoAuthService $auth)
    {
        $this->auth    = $auth;
        $this->apiBase = rtrim((string) config('zoho.api_base'), '/');
    }

    /**
     * Create an Account in Zoho CRM based on a local customer record.
     *
     * @param array $customer [
     *     'first_name' => string,
     *     'last_name'  => string,
     *     'email'      => string,
     * ]
     *
     * Mapping:
     *   Account_Name = first_name + " " + last_name
     *   Email        = email (if provided)
     *
     * @return array Full Zoho API response (decoded JSON)
     *
     * @throws RuntimeException if the API request fails
     */
    public function createAccountFromCustomer(array $customer): array
    {
        $payload = [
            config('zoho.fields.account_name') => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
        ];

        if (!empty($customer['email'])) {
            $payload[config('zoho.fields.email')] = (string) $customer['email'];
        }

        return $this->post(config('zoho.endpoints.accounts'), ['data' => [$payload]]);
    }

    /**
     * Create a Deal in Zoho CRM and link it to a specific Account.
     *
     * @param array       $deal          Associative array with local deal fields:
     *                                   - name (string)      Deal title (fallback: "Untitled Deal")
     *                                   - customer_id (int)  Local customer id (not sent to Zoho)
     *                                   - source (string)    Source label (not sent to Zoho)
     * @param string      $zohoAccountId Zoho Account record ID to link via lookup field.
     * @param string|null $zohoOwnerId   Optional Zoho User ID to set as Owner.
     * @param string|null $managerEmail  Optional manager email written to the custom field defined
     *                                   in config('zoho.fields.manager_email') (e.g., "Manager_Email").
     *
     * @return array                     Decoded Zoho API response (JSON as array).
     *
     * @throws \RuntimeException         If the HTTP request fails or Zoho returns an error.
     */
    public function createDealForAccount(
        array $deal,
        string $zohoAccountId,
        ?string $zohoOwnerId = null,
        ?string $managerEmail = null
    ): array {
        // Build base payload required by Zoho Deals
        $payload = [
            config('zoho.fields.deal_name')      => (string)($deal['name'] ?? 'Untitled Deal'),
            config('zoho.fields.account_lookup') => ['id' => $zohoAccountId], // lookup to Account
            config('zoho.fields.stage')          => config('zoho.defaults.stage'),
        ];

        // Optional: custom field for manager email
        if (!empty($managerEmail)) {
            $payload[(string) config('zoho.fields.manager_email')] = $managerEmail;
        }

        // Optional: assign Zoho Owner (user) if provided
        if (!empty($zohoOwnerId)) {
            $payload[config('zoho.fields.owner')] = ['id' => $zohoOwnerId];
        }

        // Send to Zoho
        return $this->post(config('zoho.endpoints.deals'), ['data' => [$payload]]);
    }

    /**
     * Push a local Deal model to Zoho CRM.
     *
     * Steps:
     *  1. Create an Account in Zoho based on local customer.
     *  2. Create a Deal in Zoho and link it to that Account.
     *  3. Optionally set manager email into custom field.
     *
     * @param \App\Models\Deal $deal Local Deal model with relations `customer` and `manager`
     *
     * @return array Response from Zoho API (decoded JSON)
     *
     * @throws RuntimeException if any Zoho API call fails
     */
    public function pushDeal(\App\Models\Deal $deal): array
    {
        // Ensure relations are loaded
        $customer = $deal->customer;
        if (!$customer) {
            throw new RuntimeException("Deal #{$deal->id} has no customer relation.");
        }

        // Build safe Account_Name (Zoho requires non-empty)
        $first = trim((string) ($customer->first_name ?? ''));
        $last  = trim((string) ($customer->last_name  ?? ''));
        $email = trim((string) ($customer->email      ?? ''));
        $accountName = trim($first.' '.$last);
        if ($accountName === '') {
            $accountName = $email !== '' ? $email : ('Customer #'.$customer->id);
        }

        // 1) Reuse existing Account if found
        $zohoAccountId = $this->findAccountIdByName($accountName);

        // 2) Otherwise create a new Account
        if (!$zohoAccountId) {
            $accountResp = $this->createAccountFromCustomer([
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => $email,
            ]);

            $zohoAccountId = $accountResp['data'][0]['details']['id'] ?? null;
            if (!$zohoAccountId) {
                throw new RuntimeException("Zoho did not return Account ID for Deal #{$deal->id}");
            }
        }

        // 3) Create Deal linked to that Account
        $managerEmail = $deal->manager?->email;

        $dealResp = $this->createDealForAccount(
            [
                'name'        => $deal->name ?? 'Untitled Deal',
                'customer_id' => $customer->id, // local context only
                'source'      => $deal->source,
            ],
            $zohoAccountId,
            null,          // Zoho OwnerId if needed
            $managerEmail
        );

        return $dealResp;
    }

    /**
     * Base POST request to Zoho API with authorization.
     *
     * @param string $path API path (e.g. /crm/v2/Accounts)
     * @param array  $body Request body (sent as JSON)
     *
     * @return array Full Zoho API response (decoded JSON)
     *
     * @throws RuntimeException if the API request fails
     */
    private function post(string $path, array $body): array
    {
        $token = $this->auth->getAccessToken();
        $base  = rtrim((string) config('zoho.api_base'), '/');
        $path  = '/' . ltrim($path, '/'); // normalize

        $resp = \Illuminate\Support\Facades\Http::withOptions(['proxy' => null])
            ->timeout((int) config('zoho.timeout', 20))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($base . $path, $body);

        if ($resp->failed()) {
            throw new RuntimeException('Zoho API POST error: ' . $resp->body());
        }
        return (array) $resp->json();
    }

    /**
     * Perform a GET request to Zoho CRM.
     *
     * @param string $path  e.g. '/crm/v3/org'
     * @param array  $query Query params
     * @return array        Decoded JSON
     */
    public function get(string $path, array $query = []): array
    {
        $token = $this->auth->getAccessToken();
        $base  = rtrim((string) config('zoho.api_base'), '/');
        $path  = '/' . ltrim($path, '/'); // normalize

        $resp = \Illuminate\Support\Facades\Http::withOptions(['proxy' => null])
            ->timeout((int) config('zoho.timeout', 20))
            ->withToken($token)
            ->get($base . $path, $query);

        if ($resp->failed()) {
            throw new RuntimeException('Zoho API GET error: ' . $resp->body());
        }
        return (array) $resp->json();
    }

    /**
     * Find account ID in Zoho CRM by account name.
     *
     * @param string $accountName The name of the account to search for.
     * @return string|null The ID of the account if found, or null if not found.
     */
    private function findAccountIdByName(string $accountName): ?string
    {
        $resp = $this->get(
            rtrim(config('zoho.endpoints.accounts'), '/').'/search',
            ['criteria' => sprintf('(Account_Name:equals:%s)', $accountName)]
        );

        return $resp['data'][0]['id'] ?? null;
    }

}
