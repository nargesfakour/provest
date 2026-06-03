<?php

declare(strict_types=1);

return [
    'api_token'       => env('ABANTETHER_API_TOKEN'),
    'api_base'        => env('ABANTETHER_API_BASE', 'https://api.abantether.com'),
    'deposit_coin'    => env('ABANTETHER_DEPOSIT_COIN', 'USDT'),
    'default_network' => env('ABANTETHER_DEFAULT_NETWORK', 'TRC20'),
];
