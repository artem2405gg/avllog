<?php
/**
 * Конфиг платежей: Tinkoff + тарифы подписок
 * 
 * БОЕВОЙ режим (боевые ключи)
 */

return [
    // ============ TINKOFF ============
    'tinkoff' => [
        'terminal_key'     => '1789664505095',                                  // ← боевой
        'secret_key'       => 'SGbPpc42KdvX2DQ_',                                       // ← боевой (свой полный)
        'api_url'          => 'https://securepay.tinkoff.ru/v2/',
        
        // Обязательные URL для Tinkoff
        'notification_url' => 'https://avllogist.ru/api/tinkoff_webhook.php',
        'success_url'      => 'https://avllogist.ru/payment/success',
        'fail_url'         => 'https://avllogist.ru/payment/fail',
        
        // Параметры чека (54-ФЗ)
        'taxation'         => 'usn_income',   // УСН доходы
        'tax'              => 'none',          // без НДС
    ],
    
    // ============ ТАРИФЫ ПОДПИСОК ============
    'subscriptions' => [
        
        // --- МЕСЯЧНЫЕ ---
        'basic' => [
            'amount'   => 1990,
            'duration' => 30,
            'name'     => 'Базовый',
        ],
        'pro' => [
            'amount'   => 4990,
            'duration' => 30,
            'name'     => 'Pro',
        ],
        
        // --- ГОДОВЫЕ (со скидкой 17%) ---
        'basic_yearly' => [
            'amount'   => 19908,
            'duration' => 365,
            'name'     => 'Базовый (год)',
        ],
        'pro_yearly' => [
            'amount'   => 49900,
            'duration' => 365,
            'name'     => 'Pro (год)',
        ],
    ],
];