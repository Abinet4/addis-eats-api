<?php

// Structure lives here and is committed. Values that differ per machine come
// from .env, read ONLY in this file (env() returns null after config:cache).
return [
    // Orders with a subtotal at or above this ship free (santim). 50000 = ETB 500.00
    'free_delivery_from' => (int) env('ADDIS_EATS_FREE_DELIVERY_FROM', 50000),

    // Delivery zones and their base fee in santim. The key list IS the zone list.
    'zones' => [
        'Bole' => 4500,
        'Kazanchis' => 4500,
        'Piassa' => 6000,
        'Megenagna' => 6000,
        'CMC' => 7000,
        'Ayat' => 7500,
    ],

    // Promo code honoured by PromoRule (10% off the delivery fee).
    'promo_code' => env('ADDIS_EATS_PROMO_CODE', 'ADDIS10'),

    // Which PricingRule the container injects: standard | promo | free
    'pricing_rule' => env('ADDIS_EATS_PRICING_RULE', 'standard'),

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],
];
