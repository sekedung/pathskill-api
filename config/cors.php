<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Path yang diizinkan diakses cross-origin. 'api/*' menutupi semua
    | endpoint kita (routes/api.php), 'sanctum/csrf-cookie' dibiarkan
    | ada meskipun kita pakai Bearer token (bukan cookie-based auth),
    | supaya tidak error kalau suatu saat butuh SPA cookie auth juga.
    |
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Origin frontend yang diizinkan
    |--------------------------------------------------------------------------
    |
    | GANTI/TAMBAH sesuai domain frontend kamu. Untuk development,
    | Next.js default jalan di localhost:3000. Untuk production nanti
    | (setelah aplikasi stabil), tambahkan domain aslinya di sini —
    | JANGAN pakai '*' kalau supports_credentials true.
    |
    */
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | FALSE karena kita pakai Bearer token di header Authorization
    | (lihat lib/api.ts di frontend), BUKAN cookie-based session auth.
    | Kalau nanti pindah ke cookie-based Sanctum SPA auth, baru ubah
    | ini jadi true DAN set SANCTUM_STATEFUL_DOMAINS di .env.
    |
    */
    'supports_credentials' => false,

];