<?php

use App\Payment\Zibal;

return [
    'payment' => [
        'active_gateway' => env('PAY_GATEWAY', Zibal::getName()),
        'gateways' => [
            Zibal::class,
        ],
        'config' => [
            'zibal' => [
                'merchant' => env('ZIBAL_MERCHANT', 'zibal'),
            ],
        ],
    ],
];
