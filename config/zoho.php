<?php

return [

    // Base URLs
    'accounts_base' => env('ZOHO_ACCOUNTS_BASE'),
    'api_base'      => env('ZOHO_API_BASE'),

    // API endpoints
    'endpoints' => [
        'token'    => '/oauth/v2/token',
        'accounts' => '/crm/v3/Accounts',
        'deals'    => '/crm/v3/Deals',
    ],

    // Fields Zoho
    'fields' => [
        'account_name'   => 'Account_Name',
        'email'          => 'Email',
        'deal_name'      => 'Deal_Name',
        'account_lookup' => 'Account_Name',
        'stage'          => 'Stage',
        'owner'          => 'Owner',
        'manager_email'  => 'Manager_Email'
    ],

    // Default values
    'defaults' => [
        'stage' => 'Qualification',
    ],

    // HTTP
    'timeout' => (int) env('ZOHO_HTTP_TIMEOUT', 20),
    'retries' => (int) env('ZOHO_HTTP_RETRIES', 2),
];
