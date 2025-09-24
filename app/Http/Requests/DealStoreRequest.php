<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealStoreRequest extends FormRequest
{
    // Anyone can call this endpoint (adjust if you have auth)
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules aligned with DB schema:
     * - deals.name: varchar(191)
     * - deals.source: enum('Source 1'..'Source 5')
     * - customers.first_name/last_name: varchar(100)
     * - customers.email: varchar(191), unique not required here
     * - customers.id: optional existing customer
     */
    public function rules(): array
    {
        return [
            // Deal
            'deal.name'   => ['required', 'string', 'max:191'],
            'deal.source' => ['required', 'string', 'in:Source 1,Source 2,Source 3,Source 4,Source 5'],

            // Either choose existing customer (id) or pass fields for a new one (fields are optional per spec)
            'customer.id'         => ['nullable', 'integer', 'exists:customers,id'],
            'customer.first_name' => ['nullable', 'string', 'max:100'],
            'customer.last_name'  => ['nullable', 'string', 'max:100'],
            'customer.email'      => ['nullable', 'email', 'max:191'],
        ];
    }

    /**
     * Optional: trim string inputs to avoid trailing spaces.
     */
    protected function prepareForValidation(): void
    {
        $deal = (array) $this->input('deal', []);
        $customer = (array) $this->input('customer', []);

        $deal['name'] = isset($deal['name']) ? trim((string) $deal['name']) : $deal['name'] ?? null;
        $deal['source'] = isset($deal['source']) ? trim((string) $deal['source']) : $deal['source'] ?? null;

        foreach (['first_name', 'last_name', 'email'] as $k) {
            if (array_key_exists($k, $customer)) {
                $customer[$k] = trim((string) $customer[$k]);
            }
        }

        $this->merge([
            'deal' => $deal,
            'customer' => $customer,
        ]);
    }
}
