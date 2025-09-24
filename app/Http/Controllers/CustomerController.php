<?php

namespace App\Http\Controllers;

use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Return customers (id, first_name, last_name, email) sorted by first_name, last_name.
     */
    public function index()
    {
        $items = Customer::query()
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $items,
        ]);
    }
}
