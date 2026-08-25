<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMS Name
    |--------------------------------------------------------------------------
    */
    'name' => env('CMS_NAME', 'Laravel CMS'),

    /*
    |--------------------------------------------------------------------------
    | Installation marker file (primary gate — works without a database)
    |--------------------------------------------------------------------------
    */
    'installed_file' => storage_path('app/cms/installed.json'),

    /*
    |--------------------------------------------------------------------------
    | Minimum PHP version
    |--------------------------------------------------------------------------
    */
    'min_php' => '8.3.0',

    /*
    |--------------------------------------------------------------------------
    | Required PHP extensions
    |--------------------------------------------------------------------------
    */
    'extensions' => [
        'pdo',
        'pdo_pgsql',
        'mbstring',
        'openssl',
        'tokenizer',
        'xml',
        'ctype',
        'json',
        'fileinfo',
        'bcmath',
    ],

    /*
    |--------------------------------------------------------------------------
    | Writable paths
    |--------------------------------------------------------------------------
    */
    'writable_paths' => [
        'storage',
        'bootstrap/cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported languages (extensible)
    |--------------------------------------------------------------------------
    */
    'languages' => [
        'en' => 'English',
        'ja' => 'Japanese',
        'my' => 'Myanmar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date formats
    |--------------------------------------------------------------------------
    */
    'date_formats' => [
        'Y-m-d' => 'YYYY-MM-DD',
        'd/m/Y' => 'DD/MM/YYYY',
        'm/d/Y' => 'MM/DD/YYYY',
        'd.m.Y' => 'DD.MM.YYYY',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI frameworks
    |--------------------------------------------------------------------------
    */
    'ui_frameworks' => [
        'tailwind' => [
            'label' => 'Tailwind CSS',
            'description' => 'Modern & Flexible',
        ],
        'bootstrap' => [
            'label' => 'Bootstrap 5',
            'description' => 'Familiar & Stable',
        ],
    ],

    'default_ui_framework' => 'tailwind',

    /*
    |--------------------------------------------------------------------------
    | Default roles created on install
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'Administrator',
        'Editor',
        'Author',
        'Contributor',
        'Subscriber',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default permissions (Phase 1 baseline)
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'manage_settings',
        'manage_users',
        'manage_roles',
        'manage_pages',
        'manage_posts',
        'manage_media',
        'manage_categories',
        'manage_menus',
        'manage_themes',
        'manage_comments',
    ],

];
