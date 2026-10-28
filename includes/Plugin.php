<?php

namespace WpConnectMcp;

use WP\MCP\Core\McpAdapter;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;
use WP\MCP\Transport\HttpTransport;
use WpConnectMcp\Abilities\CreatePost;
use WpConnectMcp\Admin\ConnectPage;
use WpConnectMcp\Abilities\GetPost;
use WpConnectMcp\Abilities\ListPosts;
use WpConnectMcp\Abilities\TrashPost;
use WpConnectMcp\Abilities\UpdatePost;

defined('ABSPATH') || exit;

final class Plugin
{
    public const CATEGORY = 'wp-connect-mcp';
    public const SERVER_ID = 'wp-connect-mcp';
    public const ROUTE_NAMESPACE = 'wp-connect-mcp';
    public const ROUTE = 'mcp';

    /**
     * @var array<class-string>
     */
    private const ABILITIES = [
        ListPosts::class,
        GetPost::class,
        CreatePost::class,
        UpdatePost::class,
        TrashPost::class,
    ];

    public static function boot(): void
    {
        if (!function_exists('wp_register_ability') || !class_exists(McpAdapter::class)) {
            add_action('admin_notices', [self::class, 'renderMissingDependencyNotice']);
            return;
        }

        add_action('wp_abilities_api_categories_init', [self::class, 'registerCategory']);
        add_action('wp_abilities_api_init', [self::class, 'registerAbilities']);
        add_action('mcp_adapter_init', [self::class, 'registerServer']);

        if (is_admin()) {
            ConnectPage::register();
        }

        McpAdapter::instance();
    }

    public static function registerCategory(): void
    {
        wp_register_ability_category(self::CATEGORY, [
            'label' => 'WP Connect MCP',
            'description' => 'Acciones limitadas sobre entradas para agentes de IA.',
        ]);
    }

    public static function registerAbilities(): void
    {
        foreach (self::ABILITIES as $ability) {
            $ability::register();
        }
    }

    public static function registerServer(McpAdapter $adapter): void
    {
        $result = $adapter->create_server(
            self::SERVER_ID,
            self::ROUTE_NAMESPACE,
            self::ROUTE,
            'WP Connect MCP',
            'Leer entradas; crear, editar, programar, publicar y enviar a la papelera solo las propias.',
            'v' . WP_CONNECT_MCP_VERSION,
            [HttpTransport::class],
            ErrorLogMcpErrorHandler::class,
            NullMcpObservabilityHandler::class,
            array_map(static function (string $ability): string {
                return $ability::NAME;
            }, self::ABILITIES)
        );

        if (is_wp_error($result)) {
            error_log('[wp-connect-mcp] ' . $result->get_error_message());
        }
    }

    public static function renderMissingDependencyNotice(): void
    {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('WP Connect MCP necesita WordPress 6.9 o superior (Abilities API).', 'wp-connect-mcp')
        );
    }
}
