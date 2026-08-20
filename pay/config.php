<?php
return [
    'api_url' => getenv('PAY_API_URL') ?: 'https://www.lg-pay.com/api/order/create',
    'secret_key' => getenv('PAY_SECRET_KEY') ?: '8A54xWqE52QIjgN5urDafq1u2s5srWns',
    'app_id' => getenv('PAY_APP_ID') ?: 'YD4065',
    'name' => getenv('PAY_MERCHANT_NAME') ?: 'damaansource-production.up.railway.app',
    'site_url' => rtrim(getenv('APP_URL') ?: 'https://damaansource-production.up.railway.app', '/'),
];
