<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Valyro - Withdraw / Points
    |--------------------------------------------------------------------------
    | Convention : 1 point = 1 centime.
    */
    'points' => [
        'min_withdraw'   => (int) env('VALYRO_MIN_WITHDRAW_POINTS', 1000),
        'max_single'     => (int) env('VALYRO_MAX_WITHDRAW_SINGLE', 5000),
        'max_per_day'    => (int) env('VALYRO_MAX_WITHDRAW_PER_DAY', 5000),
        'max_count_day'  => (int) env('VALYRO_MAX_WITHDRAW_COUNT_PER_DAY', 2),
        'cooldown_h'     => (int) env('VALYRO_WITHDRAW_COOLDOWN_HOURS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Valyro - Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => env('VALYRO_TIMEZONE', env('APP_TIMEZONE', 'Europe/Paris')),

    /*
    |--------------------------------------------------------------------------
    | Valyro - Risk / Anti-fraude
    |--------------------------------------------------------------------------
    */
    'risk' => [
        'test_mode'   => filter_var(env('VALYRO_RISK_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN),

        'force_level' => (function () {
            $v = env('VALYRO_FORCE_RISK_LEVEL', null);
            if ($v === null) return null;

            $v = strtolower(trim((string) $v));
            return in_array($v, ['low', 'medium', 'high'], true) ? $v : null;
        })(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'email' => env('VALYRO_ADMIN_EMAIL', 'maillet.clement.ifsi@gmail.com'),

        'expose_admin_note_to_user' => filter_var(
            env('VALYRO_EXPOSE_ADMIN_NOTE_TO_USER', false),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Offerwall (BitLabs)
    |--------------------------------------------------------------------------
    */
    'offerwall' => [
        'provider'    => env('VALYRO_OFFERWALL_PROVIDER', 'bitlabs'),
        'url'         => env('BITLABS_OFFERWALL_URL', ''),
        'subid_param' => env('BITLABS_SUBID_PARAM', 'subid'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Postback (CPA)
    |--------------------------------------------------------------------------
    */
    'postback' => [
        'token' => env('VALYRO_POSTBACK_TOKEN', ''),
        'allow_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('VALYRO_POSTBACK_ALLOW_IPS', ''))
        ))),

        // ✅ Rate limit (RouteServiceProvider)
        'rate_per_minute' => (int) env('VALYRO_POSTBACK_RATE_PER_MINUTE', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limits divers (RouteServiceProvider)
    |--------------------------------------------------------------------------
    */
    'ratelimits' => [
        'offers_start_per_minute' => (int) env('VALYRO_OFFERS_START_RATE_PER_MINUTE', 12),
        'withdraw_per_minute'     => (int) env('VALYRO_WITHDRAW_RATE_PER_MINUTE', 6),
    ],

];
