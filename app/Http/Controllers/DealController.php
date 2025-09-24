<?php

namespace App\Http\Controllers;

use App\Http\Requests\DealStoreRequest;
use Illuminate\Http\JsonResponse;
use App\Models\Customer;
use App\Models\Deal;
use App\Services\Zoho\ZohoCrmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DealController extends Controller
{

    /**
     * Store a new deal in the database and push it to Zoho CRM.
     *
     * @param  DealStoreRequest   $request  Validated request with deal and customer data.
     * @param  ZohoCrmService     $zoho     Service for interacting with Zoho CRM API.
     *
     * @return JsonResponse  JSON response with created Deal, resolved Customer and Zoho response.
     */
    public function store(DealStoreRequest $request, ZohoCrmService $zoho): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $zoho) {
                $customer = $this->resolveOrCreateCustomer($request->input('customer', []));

                $deal = Deal::create([
                    'name'        => (string) $request->input('deal.name'),
                    'source'      => (string) $request->input('deal.source'),
                    'customer_id' => $customer->id,
                ]);

                $zohoResponse = $zoho->pushDeal($deal);

                return response()->json([
                    'message'       => 'Deal created successfully and pushed to Zoho',
                    'deal'          => $deal,
                    'customer'      => $customer,
                    'zoho_response' => $zohoResponse,
                ], 201);
            });
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to create deal or push to Zoho',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve an existing customer by id, email or name, or create a new one.
     *
     * @param  array{
     *     id?: int|string|null,
     *     first_name?: string|null,
     *     last_name?: string|null,
     *     email?: string|null
     * } $c  Raw customer data from the request (from form inputs).
     *
     * @return Customer  The resolved or newly created Customer model.
     */
    private function resolveOrCreateCustomer(array $c): Customer
    {
        // 1) by id
        if (!empty($c['id'])) {
            return Customer::findOrFail((int) $c['id']);
        }

        // normalize & limit to DB lengths
        $first = Str::limit(trim((string) ($c['first_name'] ?? '')), 100, '');
        $last  = Str::limit(trim((string) ($c['last_name']  ?? '')), 100, '');
        $email = Str::limit(trim((string) ($c['email']      ?? '')), 191, '');

        // 2) by email (most reliable)
        if ($email !== '') {
            if ($existing = Customer::where('email', $email)->first()) {
                $updated = false;
                if ($first !== '' && !$existing->first_name) { $existing->first_name = $first; $updated = true; }
                if ($last  !== '' && !$existing->last_name)  { $existing->last_name  = $last;  $updated = true; }
                if ($updated) { $existing->save(); }
                return $existing;
            }
        }

        // 3) by exact first+last (when email empty or not found)
        if ($first !== '' || $last !== '') {
            if ($match = Customer::where('first_name', $first)->where('last_name', $last)->first()) {
                if ($email !== '' && !$match->email) { $match->email = $email; $match->save(); }
                return $match;
            }
        }

        // 4) create new
        return Customer::create([
            'first_name' => $first,
            'last_name'  => $last,
            'email'      => $email !== '' ? $email : null,
        ]);
    }
}
