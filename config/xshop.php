<?php
return [
    "payment" => [
        'active_gateway' => env('PAY_GATEWAY', \App\Payment\Zibal::getName()),
        'gateways' => [
            \App\Payment\Zibal::class,
        ],
        'config' => [
            'zibal' => [
                'merchant' => env('ZIBAL_MERCHANT', 'zibal'),
            ]
        ],
    ]
];
