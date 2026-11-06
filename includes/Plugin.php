<?php

namespace WpConnectMcp;

use WP\MCP\Core\McpAdapter;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;
use WP\MCP\Transport\HttpTransport;
use WpConnectMcp\Abilities\CreateCategory;
use WpConnectMcp\Abilities\CreatePost;
use WpConnectMcp\Admin\ConnectPage;
use WpConnectMcp\Admin\GrantsPage;
use WpConnectMcp\Abilities\GetPost;
use WpConnectMcp\Abilities\GetCurrentUser;
use WpConnectMcp\Abilities\GetPublished;
use WpConnectMcp\Abilities\GetTemplateStatus;
use WpConnectMcp\Abilities\ListCategories;
use WpConnectMcp\Abilities\ListPages;
use WpConnectMcp\Abilities\ListPosts;
use WpConnectMcp\Abilities\ListPublishedPosts;
use WpConnectMcp\Abilities\ListTemplates;
use WpConnectMcp\Abilities\SchedulePosts;
use WpConnectMcp\Abilities\TrashPost;
use WpConnectMcp\Abilities\UpdateMedia;
use WpConnectMcp\Abilities\UpdatePost;
use WpConnectMcp\Abilities\UploadMedia;
use WpConnectMcp\Security\ApplicationPasswordScope;
use WpConnectMcp\Security\Grants;
use WpConnectMcp\Security\Guard;

defined('ABSPATH') || exit;

final class Plugin
{
    public const CATEGORY = 'wp-connect-mcp';
    public const SERVER_ID = 'wp-connect-mcp';
    public const ROUTE_NAMESPACE = 'wp-connect-mcp';
    public const ROUTE = 'mcp';

    /**
     * Cada ability debe estar también en un paquete de Security\Grants::PACKAGES.
     *
     * @var array<class-string>
     */
    private const ABILITIES = [
        ListPosts::class,
        ListCategories::class,
        GetPost::class,
        GetCurrentUser::class,
        GetTemplateStatus::class,
        CreatePost::class,
        UpdatePost::class,
        SchedulePosts::class,
        UploadMedia::class,
        UpdateMedia::class,
        TrashPost::class,
        CreateCategory::class,
        ListPages::class,
        ListPublishedPosts::class,
        GetPublished::class,
        ListTemplates::class,
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
        add_filter('mcp_adapter_tools_list', [Guard::class, 'filterTools'], 10, 2);
        add_filter('mcp_adapter_create_default_server', [self::class, 'defaultServer'], 5);
        add_action('init', [Grants::class, 'migrate']);
        ApplicationPasswordScope::register();

        if (is_admin()) {
            ConnectPage::register();
            GrantsPage::register();
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
            'Leer entradas y categorías; crear, editar, programar, publicar, subir imágenes y enviar a la papelera solo contenido propio. Según los permisos del usuario, también crear categorías, leer las páginas y entradas publicadas del sitio (para el enlazado, leer primero el esquema con get-published y el contenido solo si hace falta) e insertar plantillas guardadas de Elementor de list-templates. Para cambiar unos fragmentos de una entrada (por ejemplo, añadir enlaces), usar replace de update-post en lugar de reenviar el contenido entero. Para programar varias entradas, usar schedule-posts en una sola llamada y no lanzar muchas llamadas en paralelo: el cortafuegos del sitio puede bloquear la IP.',
            'v' . WP_CONNECT_MCP_VERSION,
            [HttpTransport::class],
            ErrorLogMcpErrorHandler::class,
            NullMcpObservabilityHandler::class,
            array_map(static function (string $ability): string {
                return $ability::NAME;
            }, self::ABILITIES),
            [],
            [],
            [Guard::class, 'canConnect']
        );

        if (is_wp_error($result)) {
            error_log('[wp-connect-mcp] ' . $result->get_error_message());
        }
    }

    /**
     * El adapter crea por defecto /wp-json/mcp/mcp-adapter-default-server, con execute-ability y acceso con
     * solo `read`. Se desactiva salvo que el sitio lo necesite (Novamira): filtro wp_connect_mcp_keep_default_server.
     */
    public static function defaultServer(bool $create): bool
    {
        return $create && (bool) apply_filters('wp_connect_mcp_keep_default_server', false);
    }

    public static function renderMissingDependencyNotice(): void
    {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('WP Connect MCP necesita WordPress 6.9 o superior (Abilities API).', 'wp-connect-mcp')
        );
    }
}
