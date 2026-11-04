<?php

namespace WpConnectMcp\Security;

use WP_Application_Passwords;
use WpConnectMcp\Abilities\CreateCategory;
use WpConnectMcp\Abilities\CreatePost;
use WpConnectMcp\Abilities\GetCurrentUser;
use WpConnectMcp\Abilities\GetPost;
use WpConnectMcp\Abilities\GetPublished;
use WpConnectMcp\Abilities\GetTemplateStatus;
use WpConnectMcp\Abilities\ListCategories;
use WpConnectMcp\Abilities\ListPages;
use WpConnectMcp\Abilities\ListPosts;
use WpConnectMcp\Abilities\ListPublishedPosts;
use WpConnectMcp\Abilities\SchedulePosts;
use WpConnectMcp\Abilities\TrashPost;
use WpConnectMcp\Abilities\UpdatePost;
use WpConnectMcp\Abilities\UploadMedia;
use WpConnectMcp\Admin\ConnectPage;

defined('ABSPATH') || exit;

/**
 * Paquetes de abilities que un administrador otorga a cada usuario. Sin paquetes, el usuario
 * no abre sesión MCP, no ve herramientas y no puede crear la conexión.
 */
final class Grants
{
    public const META = 'wp_connect_mcp_packages';
    public const CHANGED_META = 'wp_connect_mcp_packages_changed';
    private const MIGRATED_OPTION = 'wp_connect_mcp_grants_migrated';

    /**
     * Cada ability nueva debe entrar en al menos un paquete; si no, nadie podrá usarla.
     *
     * @var array<string, array{label: string, description: string, abilities: list<string>}>
     */
    public const PACKAGES = [
        'posts-lectura' => [
            'label' => 'Leer',
            'description' => 'Listar y leer sus entradas, categorías, usuario conectado y plantillas del blog.',
            'abilities' => [
                ListPosts::NAME,
                ListCategories::NAME,
                GetPost::NAME,
                GetCurrentUser::NAME,
                GetTemplateStatus::NAME,
            ],
        ],
        'posts-escritura' => [
            'label' => 'Escribir',
            'description' => 'Crear, editar, programar y publicar sus entradas (según su rol) y subir imágenes.',
            'abilities' => [
                CreatePost::NAME,
                UpdatePost::NAME,
                SchedulePosts::NAME,
                UploadMedia::NAME,
            ],
        ],
        'posts-papelera' => [
            'label' => 'Papelera',
            'description' => 'Enviar a la papelera sus entradas, también publicadas.',
            'abilities' => [
                TrashPost::NAME,
            ],
        ],
        'categorias-escritura' => [
            'label' => 'Categorías y etiquetas',
            'description' => 'Crear categorías nuevas y asignar etiquetas que aún no existen (sin este paquete, solo las existentes).',
            'abilities' => [
                CreateCategory::NAME,
            ],
        ],
        'sitio-lectura' => [
            'label' => 'Leer el sitio',
            'description' => 'Listar y leer las páginas y las entradas publicadas de cualquier autor, para el enlazado interno. Solo lo publicado, lo mismo que ve un visitante.',
            'abilities' => [
                ListPages::NAME,
                ListPublishedPosts::NAME,
                GetPublished::NAME,
            ],
        ],
    ];

    /**
     * @return list<string>
     */
    public static function packagesOf(int $userId): array
    {
        $saved = get_user_meta($userId, self::META, true);

        return array_values(array_intersect(array_keys(self::PACKAGES), is_array($saved) ? $saved : []));
    }

    public static function hasAny(int $userId): bool
    {
        return $userId > 0 && [] !== self::packagesOf($userId);
    }

    public static function allows(string $ability, int $userId): bool
    {
        return in_array($ability, self::abilitiesOf($userId), true);
    }

    /**
     * @return list<string>
     */
    public static function abilitiesOf(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $abilities = [];
        foreach (self::packagesOf($userId) as $package) {
            $abilities = array_merge($abilities, self::PACKAGES[$package]['abilities']);
        }

        return array_values(array_unique($abilities));
    }

    /**
     * Guarda los paquetes de un usuario. Si se queda sin ninguno, se revocan sus conexiones.
     *
     * @param list<string> $packages
     * @return int Conexiones revocadas.
     */
    public static function set(int $userId, array $packages, int $changedBy): int
    {
        $packages = array_values(array_intersect(array_keys(self::PACKAGES), $packages));
        $before = self::packagesOf($userId);

        if ($packages === $before) {
            return 0;
        }

        update_user_meta($userId, self::META, $packages);
        update_user_meta($userId, self::CHANGED_META, ['by' => $changedBy, 'at' => time()]);
        AuditLog::add($changedBy, 'grants', $userId, sprintf(
            '%s → %s',
            $before ? implode(', ', $before) : '—',
            $packages ? implode(', ', $packages) : '—'
        ));

        return $packages ? 0 : self::revokeConnections($userId, $changedBy);
    }

    public static function revokeConnections(int $userId, int $changedBy): int
    {
        $revoked = 0;

        foreach (self::connectionsOf($userId) as $item) {
            if (true === WP_Application_Passwords::delete_application_password($userId, $item['uuid'])) {
                $revoked++;
            }
        }

        if ($revoked) {
            AuditLog::add($changedBy, 'revoke', $userId, sprintf('%d conexión(es)', $revoked));
        }

        return $revoked;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function connectionsOf(int $userId): array
    {
        return array_values(array_filter(
            WP_Application_Passwords::get_user_application_passwords($userId),
            static function (array $item): bool {
                return ConnectPage::APP_ID === ($item['app_id'] ?? '');
            }
        ));
    }

    /**
     * Paquetes que la migración de la 0.3.0 dio a quien ya tenía una conexión. Los añadidos después
     * (categorías, leer el sitio) no se dan a nadie automáticamente: los otorga un administrador.
     */
    private const MIGRATED_PACKAGES = ['posts-lectura', 'posts-escritura', 'posts-papelera'];

    /**
     * Al pasar a 0.3.0, quien ya tenía una conexión creada desde la página recibe los paquetes de entradas,
     * para que las conexiones existentes sigan funcionando. Queda anotado en el registro.
     */
    public static function migrate(): void
    {
        if (get_option(self::MIGRATED_OPTION)) {
            return;
        }

        update_option(self::MIGRATED_OPTION, WP_CONNECT_MCP_VERSION);

        $userIds = get_users(['meta_key' => '_application_passwords', 'fields' => 'ID']);
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if (self::connectionsOf($userId) && !get_user_meta($userId, self::META, true)) {
                self::set($userId, self::MIGRATED_PACKAGES, 0);
            }
        }
    }
}
