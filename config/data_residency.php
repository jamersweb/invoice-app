<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Residency Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where data should be stored for compliance with regional
    | data residency requirements (UAE/GCC).
    |
    */

    'region' => env('DATA_RESIDENCY_REGION', 'UAE'),

    'allowed_regions' => ['UAE', 'GCC', 'US', 'EU'],

    'storage_region' => env('AWS_REGION', 'me-south-1'), // Middle East (Bahrain)

    'database_region' => env('DB_REGION', 'me-south-1'),

    // Encryption at rest
    'encrypt_sensitive_data' => env('ENCRYPT_SENSITIVE_DATA', true),

    // Fields that should be encrypted
    'encrypted_fields' => [
        'suppliers' => ['tax_registration_number', 'contact_phone', 'kyc_data'],
        'buyers' => ['tax_registration_number', 'contact_phone', 'metadata'],
        'users' => ['email', 'name'],
    ],

    // Data retention periods (in years)
    'retention' => [
        'audit_events' => env('AUDIT_RETENTION_YEARS', 7),
        'analytics_events' => env('ANALYTICS_RETENTION_YEARS', 1),
        'soft_deleted' => env('SOFT_DELETE_RETENTION_YEARS', 2),
    ],
];









