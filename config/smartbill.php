<?php

return [
    // SmartBill account email (used as Basic Auth username)
    'email' => env('SMARTBILL_EMAIL'),

    // SmartBill API token (used as Basic Auth password)
    // Generate at: SmartBill → Configurari → Integrari → Token API
    'token' => env('SMARTBILL_TOKEN'),

    // Your company CUI / VAT code registered in SmartBill
    'company_vat_code' => env('SMARTBILL_COMPANY_VAT_CODE'),
];
