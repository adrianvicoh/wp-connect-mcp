<?php

/**
 * Plugin Name:       WP Connect MCP
 * Description:       Conecta agentes de IA a WordPress con acceso limitado a las acciones de cada tarea.
 * Version:           0.2.1
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-connect-mcp
 */

defined('ABSPATH') || exit;

define('WP_CONNECT_MCP_VERSION', '0.2.1');
define('WP_CONNECT_MCP_FILE', __FILE__);

require_once __DIR__ . '/vendor/autoload_packages.php';

add_action('plugins_loaded', [\WpConnectMcp\Plugin::class, 'boot']);
