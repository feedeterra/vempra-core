<?php
/**
 * Plugin Name:  Vempra Core
 * Description:  La tienda de Vempra en codigo: precios, textos, portada, pie, checkout, fichas y todo lo que antes vivia en Code Snippets.
 * Version:      1.10.0
 * Author:       Vempra
 * Text Domain:  vempra-core
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'VEMPRA_CORE_VERSION', '1.10.0' );
define( 'VEMPRA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VEMPRA_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * El post type de las paginas de tour y el meta que las vincula con su
 * producto de WooCommerce. Si el theme cambia estos nombres, se cambian aca
 * y no hay que tocar nada mas.
 */
define( 'VEMPRA_TOUR_CPT',      'tour' );
define( 'VEMPRA_META_PRODUCTO', 'tour_booking_product' );
define( 'VEMPRA_META_PRECIO',   'tour_price' );

require_once VEMPRA_CORE_DIR . 'inc/textos.php';
require_once VEMPRA_CORE_DIR . 'inc/precios.php';
require_once VEMPRA_CORE_DIR . 'inc/admin.php';
require_once VEMPRA_CORE_DIR . 'inc/tarjetas.php';
require_once VEMPRA_CORE_DIR . 'inc/accesibilidad.php';
require_once VEMPRA_CORE_DIR . 'inc/actualizador.php';
require_once VEMPRA_CORE_DIR . 'inc/assets.php';
require_once VEMPRA_CORE_DIR . 'inc/frontend.php';
require_once VEMPRA_CORE_DIR . 'inc/sitio.php';
require_once VEMPRA_CORE_DIR . 'inc/portada.php';
require_once VEMPRA_CORE_DIR . 'inc/paginas.php';
require_once VEMPRA_CORE_DIR . 'inc/tours.php';
require_once VEMPRA_CORE_DIR . 'inc/tienda.php';
require_once VEMPRA_CORE_DIR . 'inc/catalogo.php';
require_once VEMPRA_CORE_DIR . 'inc/snippets.php';

/**
 * WooCommerce es imprescindible: sin el no hay de donde leer los precios.
 */
add_action( 'admin_notices', function () {
	if ( class_exists( 'WooCommerce' ) ) { return; }
	echo '<div class="notice notice-error"><p><strong>Vempra Core</strong> necesita WooCommerce activo para funcionar.</p></div>';
} );

/**
 * Al activar y al actualizar, el mapa de precios se tira a la basura.
 *
 * El mapa se cachea doce horas. Sin esto, instalar una version nueva del
 * plugin dejaba el sitio mostrando los precios que estaban vigentes cuando
 * se lleno el cache, y no habia forma evidente de forzarlo desde el panel.
 */
function vempra_core_limpiar_cache() {
	delete_transient( 'vempra_mapa_precios' );
}

register_activation_hook( __FILE__, 'vempra_core_limpiar_cache' );

add_action( 'upgrader_process_complete', 'vempra_core_limpiar_cache' );

/**
 * Y una vez por version: cubre el caso de subir los archivos por FTP o por
 * el administrador de archivos del hosting, donde no corre ningun hook de
 * instalacion.
 */
add_action( 'init', function () {
	if ( get_option( 'vempra_core_version_vista' ) === VEMPRA_CORE_VERSION ) { return; }
	vempra_core_limpiar_cache();
	update_option( 'vempra_core_version_vista', VEMPRA_CORE_VERSION );
} );
