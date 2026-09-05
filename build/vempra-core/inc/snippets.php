<?php
/**
 * CODE SNIPPETS: apagado de los snippets que este plugin reemplaza.
 *
 * Hasta la 1.8.0 el sitio tenia 64 snippets en el plugin Code Snippets, con
 * el CSS, el JavaScript y el PHP de la portada, el pie, el checkout y las
 * fichas. Desde la 1.9.0 todo eso vive en estos archivos. Si los snippets
 * siguieran activos, cada cosa saldria dos veces (dos pies de pagina, dos
 * botones de WhatsApp, dos barras de nieve).
 *
 * Por eso, una sola vez al activarse esta version, se apagan los snippets de
 * la lista. No se borran: quedan en Snippets > Todos los snippets como
 * inactivos, por si hay que mirar como era algo. Borrarlos es tarea manual.
 *
 * Code Snippets ejecuta los snippets activos en plugins_loaded con prioridad
 * normal; esto corre en plugins_loaded con prioridad 0, asi que se apagan
 * antes de que lleguen a correr en esa misma carga.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * IDs de los snippets migrados (estan en la exportacion del 2026-09-04).
 * Los que no figuran aca ya estaban apagados o eran tareas de una sola vez.
 */
function vempra_snippets_migrados() {
	return array(
		9, 10, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27,
		28, 29, 30, 31, 32, 33, 34, 36, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48,
		49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 62, 63, 64, 65, 66,
	);
}

add_action( 'plugins_loaded', function () {
	if ( 'v1.9.0' === get_option( 'vempra_snippets_migrados' ) ) { return; }

	global $wpdb;
	$tabla = $wpdb->prefix . 'snippets';
	$hay   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla ) );
	if ( $hay !== $tabla ) {
		// Sin Code Snippets no hay nada que apagar; se anota igual para no
		// volver a consultar en cada carga.
		update_option( 'vempra_snippets_migrados', 'v1.9.0', false );
		return;
	}

	$ids = implode( ',', array_map( 'intval', vempra_snippets_migrados() ) );
	$wpdb->query( "UPDATE {$tabla} SET active = 0 WHERE id IN ({$ids})" );

	// Code Snippets guarda copias de los snippets activos en cache; hay que
	// vaciarlas para que no los ejecute igual desde ahi.
	wp_cache_delete( 'all_snippets', 'code_snippets' );
	wp_cache_delete( 'active_snippets', 'code_snippets' );
	delete_transient( 'code_snippets_active' );

	update_option( 'vempra_snippets_migrados', 'v1.9.0', false );
}, 0 );

// Aviso en el panel para que se sepa que paso.
add_action( 'admin_notices', function () {
	if ( 'v1.9.0' !== get_option( 'vempra_snippets_migrados' ) || get_option( 'vempra_snippets_aviso_visto' ) ) { return; }
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	if ( ! empty( $_GET['vempra_aviso_visto'] ) ) {
		update_option( 'vempra_snippets_aviso_visto', 1, false );
		return;
	}
	$url = esc_url( add_query_arg( 'vempra_aviso_visto', '1' ) );
	echo '<div class="notice notice-info"><p><strong>Vempra Core 1.9.0:</strong> los ' . count( vempra_snippets_migrados() ) . ' snippets que se mudaron al plugin quedaron desactivados en Code Snippets. Cuando verifiques que el sitio se ve bien, podes borrarlos desde Snippets. <a href="' . $url . '">Entendido</a></p></div>';
} );
