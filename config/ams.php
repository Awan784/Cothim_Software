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

    'product_name' => env('AMS_PRODUCT_NAME', 'Contimade Traders'),
    'company_name' => env('AMS_COMPANY_NAME', env('APP_NAME', 'Contimade Traders')),
    'default_vat_rate' => 15,
    'trial_days' => 14,
    'whatsapp' => env('AMS_WHATSAPP', '966500000000'),
    'support_phone' => env('AMS_SUPPORT_PHONE', '+966 11 510 7135'),
    'support_email' => env('AMS_SUPPORT_EMAIL', 'support@wafi.sa'),

    'date_format' => env('AMS_DATE_FORMAT', 'd-m-y'),

    'datetime_format' => env('AMS_DATETIME_FORMAT', 'd-m-y H:i'),

    'print_warranty' => "The Medicines Sold and Marketed by {company} are Manufactured by Homoeopathic Form 6 Holder Companies. These Medicines are Manufactured According to DRAP Rules under High Qualified Staff.\n\nExcept Cosmetics Products.",
    'print_note' => 'Expired products shall not be taken back and make order according to your demand. Thank you!',
    'print_on_behalf' => 'On Behalf {company}',
    'print_developed_by' => 'software developed by sentax lab (Software Company) +92-306-6400081',
    'invoice_series' => 1000,

];
