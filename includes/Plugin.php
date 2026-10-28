<?php

namespace Wwwarcelona\WpConnect;

use WP\MCP\Core\McpAdapter;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;
use WP\MCP\Transport\HttpTransport;
use Wwwarcelona\WpConnect\Abilities\CreatePost;
use Wwwarcelona\WpConnect\Admin\ConnectPage;
use Wwwarcelona\WpConnect\Abilities\GetPost;
use Wwwarcelona\WpConnect\Abilities\ListPosts;
use Wwwarcelona\WpConnect\Abilities\TrashPost;
use Wwwarcelona\WpConnect\Abilities\UpdatePost;

defined('ABSPATH') || exit;

final class Plugin
{
    public const CATEGORY = 'wwwarcelona-wp-connect';
    public const SERVER_ID = 'wwwarcelona-wp-connect';
    public const ROUTE_NAMESPACE = 'wwwarcelona';
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
            'label' => 'wwwarcelona WP Connect',
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
            'wwwarcelona WP Connect',
            'Leer entradas; crear, editar, programar, publicar y enviar a la papelera solo las propias.',
            'v' . WWWARCELONA_WP_CONNECT_VERSION,
            [HttpTransport::class],
            ErrorLogMcpErrorHandler::class,
            NullMcpObservabilityHandler::class,
            array_map(static function (string $ability): string {
                return $ability::NAME;
            }, self::ABILITIES)
        );

        if (is_wp_error($result)) {
            error_log('[wwwarcelona-wp-connect] ' . $result->get_error_message());
        }
    }

    public static function renderMissingDependencyNotice(): void
    {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('wwwarcelona WP Connect necesita WordPress 6.9 o superior (Abilities API).', 'wwwarcelona-wp-connect')
        );
    }
}
