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

    /*
    |--------------------------------------------------------------------------
    | Active theme (Blade views under resources/views/themes/{theme})
    |--------------------------------------------------------------------------
    */
    'theme' => env('CMS_THEME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Reserved URL slugs (cannot be used by Static Pages)
    |--------------------------------------------------------------------------
    */
    'reserved_slugs' => [
        'admin',
        'api',
        'setup',
        'login',
        'logout',
        'register',
        'blog',
        'posts',
        'category',
        'tag',
        'author',
        'search',
        'archive',
        'sitemap.xml',
        'robots.txt',
        'up',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic page types (systems/templates — not Static Page content)
    |--------------------------------------------------------------------------
    */
    'dynamic_page_types' => [
        'blog' => [
            'label' => 'Blog Archive',
            'default_title' => 'Blog',
            'default_url' => '/blog',
            'default_layout' => 'list',
            'default_posts_per_page' => 12,
            'view' => 'dynamic.blog',
        ],
        'post' => [
            'label' => 'Single Post',
            'default_title' => 'Post',
            'default_url' => null,
            'default_layout' => 'standard',
            'view' => 'dynamic.post',
        ],
        'category' => [
            'label' => 'Category Archive',
            'default_title' => 'Category',
            'default_url' => '/category',
            'default_layout' => 'list',
            'default_posts_per_page' => 12,
            'view' => 'dynamic.category',
        ],
        'tag' => [
            'label' => 'Tag Archive',
            'default_title' => 'Tag',
            'default_url' => '/tag',
            'default_layout' => 'list',
            'default_posts_per_page' => 12,
            'view' => 'dynamic.tag',
        ],
        'author' => [
            'label' => 'Author Archive',
            'default_title' => 'Author',
            'default_url' => '/author',
            'default_layout' => 'list',
            'default_posts_per_page' => 12,
            'view' => 'dynamic.author',
        ],
        'search' => [
            'label' => 'Search Results',
            'default_title' => 'Search',
            'default_url' => '/search',
            'default_layout' => 'list',
            'default_posts_per_page' => 12,
            'default_seo_robots' => 'noindex, follow',
            'view' => 'dynamic.search',
        ],
        'archive' => [
            'label' => 'Date Archive',
            'default_title' => 'Archives',
            'default_url' => '/archive',
            'default_layout' => 'list',
            'default_posts_per_page' => 12,
            'view' => 'dynamic.archive',
        ],
        '404' => [
            'label' => '404',
            'default_title' => 'Page Not Found',
            'default_url' => null,
            'default_message' => "Sorry, the page you're looking for doesn't exist.",
            'default_button_label' => 'Go Home',
            'default_button_url' => '/',
            'view' => 'dynamic.404',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default SEO title/description templates for dynamic pages
    |--------------------------------------------------------------------------
    | Variables: {site_name}, {post_title}, {category_name}, {tag_name},
    | {author_name}, {search_query}, {archive_label}, {page_title}
    |--------------------------------------------------------------------------
    */
    'seo_templates' => [
        'post' => [
            'title' => '{post_title} — {site_name}',
            'description' => '{post_excerpt}',
        ],
        'page' => [
            'title' => '{page_title} — {site_name}',
            'description' => '{page_excerpt}',
        ],
        'blog' => [
            'title' => '{page_title} — {site_name}',
            'description' => 'Latest articles from {site_name}.',
        ],
        'category' => [
            'title' => '{category_name} — {site_name}',
            'description' => 'Latest {category_name} articles from {site_name}.',
        ],
        'tag' => [
            'title' => '{tag_name} — {site_name}',
            'description' => 'Posts tagged {tag_name} from {site_name}.',
        ],
        'author' => [
            'title' => 'Posts by {author_name} — {site_name}',
            'description' => 'Articles written by {author_name}.',
        ],
        'search' => [
            'title' => 'Search results for "{search_query}" — {site_name}',
            'description' => 'Search results for {search_query}.',
        ],
        'archive' => [
            'title' => 'Archive: {archive_label} — {site_name}',
            'description' => 'Posts from {archive_label}.',
        ],
        '404' => [
            'title' => 'Page Not Found — {site_name}',
            'description' => 'The requested page could not be found.',
        ],
    ],

];
