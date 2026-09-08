<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WordPress embed (real WP plugins run inside WordPress, not Laravel)
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('WP_EMBED_ENABLED', false),

    'base_url' => rtrim((string) env('WP_EMBED_URL', 'http://localhost:8080'), '/'),

    'admin_url' => rtrim((string) env('WP_EMBED_ADMIN_URL', env('WP_EMBED_URL', 'http://localhost:8080').'/wp-admin'), '/'),

    'bridge_token' => (string) env('WP_BRIDGE_TOKEN', ''),

    'bridge_path' => '/wp-json/laravelpress/v1',

    'timeout' => (int) env('WP_BRIDGE_TIMEOUT', 15),

];
