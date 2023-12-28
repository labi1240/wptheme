<?php
/**
 * Plugin Name: Voxel Elements
 * Description: Custom version of Elementor built for Voxel
 * Plugin URI: https://getvoxel.io
 * Author: 27collective
 * Version: 1.3.0
 *
 * Text Domain: elementor
 *
 * @package Elementor
 * @category Core
 *
 * Elementor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Elementor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ELEMENTOR_VERSION', '3.18.2-vx.1' );

define( 'ELEMENTOR__FILE__', __FILE__ );
define( 'ELEMENTOR_PLUGIN_BASE', plugin_basename( ELEMENTOR__FILE__ ) );
define( 'ELEMENTOR_PATH', plugin_dir_path( ELEMENTOR__FILE__ ) );

if ( defined( 'ELEMENTOR_TESTS' ) && ELEMENTOR_TESTS ) {
	define( 'ELEMENTOR_URL', 'file://' . ELEMENTOR_PATH );
} else {
	define( 'ELEMENTOR_URL', plugins_url( '/', ELEMENTOR__FILE__ ) );
}

define( 'ELEMENTOR_MODULES_PATH', plugin_dir_path( ELEMENTOR__FILE__ ) . '/modules' );
define( 'ELEMENTOR_ASSETS_PATH', ELEMENTOR_PATH . 'assets/' );
define( 'ELEMENTOR_ASSETS_URL', ELEMENTOR_URL . 'assets/' );

add_action( 'plugins_loaded', 'elementor_load_plugin_textdomain' );

add_filter( 'voxel/custom-elementor-settings-parser', '__return_false' );

if ( ! version_compare( PHP_VERSION, '7.3', '>=' ) ) {
	add_action( 'admin_notices', 'elementor_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '6.0', '>=' ) ) {
	add_action( 'admin_notices', 'elementor_fail_wp_version' );
} else {
	require ELEMENTOR_PATH . 'includes/plugin.php';
}

/**
 * Load Elementor textdomain.
 *
 * Load gettext translate for Elementor text domain.
 *
 * @since 1.0.0
 *
 * @return void
 */
function elementor_load_plugin_textdomain() {
	load_plugin_textdomain( 'elementor' );
}

/**
 * Elementor admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @since 1.0.0
 *
 * @return void
 */
function elementor_fail_php_version() {
	$message = sprintf(
		/* translators: 1: `<h3>` opening tag, 2: `</h3>` closing tag, 3: PHP version. 4: Link opening tag, 5: Link closing tag. */
		esc_html__( '%1$sElementor isn’t running because PHP is outdated.%2$s Update to PHP version %3$s and get back to creating! %4$sShow me how%5$s', 'elementor' ),
		'<h3>',
		'</h3>',
		'7.3',
		'<a href="https://go.elementor.com/wp-dash-update-php/" target="_blank">',
		'</a>'
	);
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * Elementor admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @since 1.5.0
 *
 * @return void
 */
function elementor_fail_wp_version() {
	$message = sprintf(
		/* translators: 1: `<h3>` opening tag, 2: `</h3>` closing tag, 3: WP version. 4: Link opening tag, 5: Link closing tag. */
		esc_html__( '%1$sElementor isn’t running because WordPress is outdated.%2$s Update to version %3$s and get back to creating! %4$sShow me how%5$s', 'elementor' ),
		'<h3>',
		'</h3>',
		'6.0',
		'<a href="https://go.elementor.com/wp-dash-update-wordpress/" target="_blank">',
		'</a>'
	);
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

add_action( 'wp_ajax_vxe_regenerate_css', function() {
	session_write_close();
	check_ajax_referer( 'vxe_regenerate_css', '_wpnonce' );

	$post = get_post( absint( $_GET['template_id'] ?? null ) );
	if ( $post ) {
		$post_css = \Elementor\Core\Files\CSS\Post::create( $post->ID );
		$post_css->update();
	}

	die;
} );

function vxe_async_regenerate_css( $template_id ) {
	wp_remote_post( add_query_arg( [
		'action' => 'vxe_regenerate_css',
		'template_id' => $template_id,
		'_wpnonce' => wp_create_nonce( 'vxe_regenerate_css' ),
	], admin_url('admin-ajax.php') ), [
		'timeout'   => 0.01,
		'blocking'  => false,
		'cookies'   => $_COOKIE,
		'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
	] );
}

add_action( 'elementor/core/files/clear_cache', function() {
	\vxe_async_regenerate_css( get_option( 'elementor_active_kit' ) );
} );

function vxe_reset_control_stack() {
	\Elementor\Plugin::$instance->controls_manager->reset_stack();
}

function vxe_is_runtime() {
	return ! (
		is_admin()
		|| \Elementor\Plugin::$instance->preview->is_preview_mode()
		|| ( $GLOBALS['_vx_writing_css'] ?? false )
	);
}

add_action( 'plugins_loaded', function() {
	remove_action( 'admin_notices', 'elementor_pro_fail_load_out_of_date' );
	remove_action( 'admin_notices', 'elementor_pro_admin_notice_upgrade_recommendation' );
}, 100 );
