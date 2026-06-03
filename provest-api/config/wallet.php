<?php

declare(strict_types=1);

return [
    /*
     * Flat fee deducted from the user's balance on every withdrawal request.
     * Stored as a bcmath-compatible string (8 decimal places).
     */
    'withdrawal_fee' => env('WITHDRAWAL_FEE', '0.00000000'),

    /*
     * Minimum withdrawal amount in USDT.
     */
    'min_withdrawal' => env('MIN_WITHDRAWAL', '10.00000000'),
];
