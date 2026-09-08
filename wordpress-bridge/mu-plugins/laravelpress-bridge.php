<?php

declare(strict_types=1);

/**
 * Plugin Name: LaravelPress Bridge
 * Description: Lets LaravelPress list/activate/install WordPress plugins over a token-authenticated REST API.
 * Version: 1.0.0
 * Author: LaravelPress
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', static function (): void {
    $auth = static function (\WP_REST_Request $request) {
        $token = (string) getenv('WP_BRIDGE_TOKEN');
        if ($token === '') {
            $token = (string) (defined('LARAVELPRESS_BRIDGE_TOKEN') ? LARAVELPRESS_BRIDGE_TOKEN : '');
        }
        $header = (string) $request->get_header('x_laravelpress_token');
        if ($token === '' || ! hash_equals($token, $header)) {
            return new \WP_Error('laravelpress_forbidden', 'Invalid bridge token.', ['status' => 403]);
        }

        return true;
    };

    register_rest_route('laravelpress/v1', '/status', [
        'methods' => 'GET',
        'permission_callback' => $auth,
        'callback' => static function () {
            return [
                'ok' => true,
                'data' => [
                    'wordpress_version' => get_bloginfo('version'),
                    'site_url' => site_url(),
                ],
            ];
        },
    ]);

    register_rest_route('laravelpress/v1', '/plugins', [
        'methods' => 'GET',
        'permission_callback' => $auth,
        'callback' => static function () {
            if (! function_exists('get_plugins')) {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
            }
            $all = get_plugins();
            $active = get_option('active_plugins', []);
            $list = [];
            foreach ($all as $file => $meta) {
                $list[] = [
                    'file' => $file,
                    'name' => $meta['Name'] ?? $file,
                    'version' => $meta['Version'] ?? '',
                    'description' => $meta['Description'] ?? '',
                    'author' => wp_strip_all_tags($meta['Author'] ?? ''),
                    'active' => in_array($file, $active, true),
                ];
            }

            return ['ok' => true, 'data' => ['plugins' => $list]];
        },
    ]);

    register_rest_route('laravelpress/v1', '/plugins/activate', [
        'methods' => 'POST',
        'permission_callback' => $auth,
        'callback' => static function (\WP_REST_Request $request) {
            if (! function_exists('activate_plugin')) {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
            }
            $plugin = (string) $request->get_param('plugin');
            $result = activate_plugin($plugin);
            if (is_wp_error($result)) {
                return ['ok' => false, 'message' => $result->get_error_message()];
            }

            return ['ok' => true, 'data' => ['plugin' => $plugin]];
        },
    ]);

    register_rest_route('laravelpress/v1', '/plugins/deactivate', [
        'methods' => 'POST',
        'permission_callback' => $auth,
        'callback' => static function (\WP_REST_Request $request) {
            if (! function_exists('deactivate_plugins')) {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
            }
            $plugin = (string) $request->get_param('plugin');
            deactivate_plugins($plugin);

            return ['ok' => true, 'data' => ['plugin' => $plugin]];
        },
    ]);

    register_rest_route('laravelpress/v1', '/plugins/install', [
        'methods' => 'POST',
        'permission_callback' => $auth,
        'callback' => static function (\WP_REST_Request $request) {
            if (! function_exists('request_filesystem_credentials')) {
                require_once ABSPATH.'wp-admin/includes/file.php';
                require_once ABSPATH.'wp-admin/includes/misc.php';
                require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
                require_once ABSPATH.'wp-admin/includes/plugin-install.php';
                require_once ABSPATH.'wp-admin/includes/plugin.php';
            }

            $files = $request->get_file_params();
            if (empty($files['package']['tmp_name'])) {
                return ['ok' => false, 'message' => 'Missing package upload.'];
            }

            $tmp = (string) $files['package']['tmp_name'];
            $skin = new \Automatic_Upgrader_Skin;
            $upgrader = new \Plugin_Upgrader($skin);
            $result = $upgrader->install($tmp);
            if (is_wp_error($result)) {
                return ['ok' => false, 'message' => $result->get_error_message()];
            }
            if ($result !== true) {
                return ['ok' => false, 'message' => 'Plugin install failed.'];
            }

            $pluginFile = $upgrader->plugin_info();
            $activate = $request->get_param('activate') === '1' || $request->get_param('activate') === true;
            if ($activate && is_string($pluginFile) && $pluginFile !== '') {
                activate_plugin($pluginFile);
            }

            return [
                'ok' => true,
                'data' => [
                    'plugin' => $pluginFile,
                ],
            ];
        },
    ]);
});
