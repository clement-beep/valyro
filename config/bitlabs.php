<?php

return [
    // Ton identifiant / hash / token BitLabs (selon leur dashboard)
    'app_id' => env('BITLABS_APP_ID', ''),
    'api_key' => env('BITLABS_API_KEY', ''),

    // URL d’embed offerwall BitLabs (à remplacer quand tu auras le lien officiel)
    // On garde un placeholder propre.
    'offerwall_url' => env('BITLABS_OFFERWALL_URL', ''),

    // Paramètre subid attendu par BitLabs (souvent "subid" ou "s1")
    'subid_param' => env('BITLABS_SUBID_PARAM', 'subid'),

    // Sécurité postback (en plus du token Valyro)
    'postback_secret' => env('BITLABS_POSTBACK_SECRET', ''),

    // Optionnel : allowlist IP BitLabs (si BitLabs te les fournit)
    'postback_allow_ips' => array_values(array_filter(array_map('trim',
        explode(',', env('BITLABS_POSTBACK_ALLOW_IPS', ''))
    ))),
];
