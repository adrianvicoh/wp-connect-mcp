<?php

/**
 * Plugin Name:       wwwarcelona WP Connect
 * Description:       Conecta agentes de IA a WordPress con acceso limitado a las acciones de cada tarea.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            wwwarcelona
 * Author URI:        https://wwwarcelona.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wwwarcelona-wp-connect
 */

defined('ABSPATH') || exit;

define('WWWARCELONA_WP_CONNECT_VERSION', '0.1.0');
define('WWWARCELONA_WP_CONNECT_FILE', __FILE__);

require_once __DIR__ . '/vendor/autoload_packages.php';

add_action('plugins_loaded', [\Wwwarcelona\WpConnect\Plugin::class, 'boot']);
