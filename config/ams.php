<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Show delete buttons in the UI
    |--------------------------------------------------------------------------
    |
    | Set AMS_SHOW_DELETE=true in .env to show delete buttons again.
    |
    */
    'show_delete_buttons' => (bool) env('AMS_SHOW_DELETE', false),

    'product_name' => env('AMS_PRODUCT_NAME', 'Wafi'),
    'company_name' => env('AMS_COMPANY_NAME', env('APP_NAME', 'Wafi')),
    'default_vat_rate' => 15,
    'trial_days' => 14,
    'whatsapp' => env('AMS_WHATSAPP', '966500000000'),
    'support_phone' => env('AMS_SUPPORT_PHONE', '+966 11 510 7135'),
    'support_email' => env('AMS_SUPPORT_EMAIL', 'support@wafi.sa'),

    'date_format' => env('AMS_DATE_FORMAT', 'd-m-y'),

    'datetime_format' => env('AMS_DATETIME_FORMAT', 'd-m-y H:i'),

];
