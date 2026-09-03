<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
    ];

    /**
     * WordPress-style capability labels for admin UI.
     *
     * @return array<string, string>
     */
    public static function capabilityLabels(): array
    {
        return [
            // Legacy CMS capabilities (kept for existing admin routes)
            'manage_settings' => 'Manage Settings',
            'manage_users' => 'Manage Users',
            'manage_roles' => 'Manage Roles',
            'manage_pages' => 'Manage Pages',
            'manage_posts' => 'Manage Posts',
            'manage_media' => 'Manage Media',
            'manage_categories' => 'Manage Categories',
            'manage_menus' => 'Manage Menus',
            'manage_themes' => 'Manage Themes',
            'manage_comments' => 'Manage Comments',
            // LaravelPress / WordPress-compatible capabilities
            'create_posts' => 'Create Posts',
            'edit_posts' => 'Edit Posts',
            'edit_others_posts' => 'Edit Others\' Posts',
            'publish_posts' => 'Publish Posts',
            'delete_posts' => 'Delete Posts',
            'create_pages' => 'Create Pages',
            'edit_pages' => 'Edit Pages',
            'edit_others_pages' => 'Edit Others\' Pages',
            'publish_pages' => 'Publish Pages',
            'delete_pages' => 'Delete Pages',
            'upload_media' => 'Upload Media',
            'moderate_comments' => 'Moderate Comments',
            'manage_options' => 'Manage Options',
            'manage_plugins' => 'Manage Plugins',
            'read' => 'Read',
        ];
    }

    /**
     * Default capability map by role name (WordPress-like).
     *
     * @return array<string, list<string>>
     */
    public static function defaultCapabilities(): array
    {
        $all = array_keys(self::capabilityLabels());

        return [
            'Administrator' => $all,
            'Editor' => array_values(array_filter(
                $all,
                fn (string $p) => ! in_array($p, [
                    'manage_settings',
                    'manage_users',
                    'manage_roles',
                    'manage_options',
                    'manage_plugins',
                ], true)
            )),
            'Author' => [
                'read',
                'create_posts',
                'edit_posts',
                'publish_posts',
                'delete_posts',
                'upload_media',
                'manage_posts',
                'manage_media',
                'manage_categories',
            ],
            'Contributor' => [
                'read',
                'create_posts',
                'edit_posts',
                'manage_posts',
            ],
            'Subscriber' => [
                'read',
            ],
        ];
    }
}
