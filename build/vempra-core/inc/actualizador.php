<?php
/**
 * ACTUALIZADOR
 *
 * Hasta la 1.7.0 cada version nueva del plugin habia que subirla a mano como
 * ZIP desde Plugins > Anadir nuevo. Desde aca el plugin se actualiza igual
 * que cualquiera del repositorio de WordPress: cuando hay una version nueva
 * publicada en GitHub aparece el aviso en Plugins y se aplica con un clic
 * (o sola, si las actualizaciones automaticas estan activadas).
 *
 * Como funciona:
 *   - Cada version es un "release" en GitHub con la etiqueta v1.8.0, v1.9.0,
 *     etc. y el ZIP del plugin adjunto.
 *   - Se consulta la API publica de GitHub como mucho una vez cada 12 horas
 *     (transient). Sin token: el repositorio es publico y el plugin no
 *     contiene ninguna credencial.
 *   - Se compara la etiqueta contra VEMPRA_CORE_VERSION; si es mayor, se le
 *     informa a WordPress la URL del ZIP.
 *   - El ZIP se arma con la carpeta vempra-core/ adentro, asi WordPress lo
 *     instala sobre la misma carpeta y no queda "vempra-core-1.8.0" al lado
 *     de la vieja. Por las dudas, upgrader_source_selection renombra la
 *     carpeta si llegara con otro nombre.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function vempra_repo_github() {
	return apply_filters( 'vempra_repo_github', 'feedeterra/vempra-core' );
}

function vempra_plugin_base() {
	return plugin_basename( VEMPRA_CORE_DIR . 'vempra-core.php' );
}

/**
 * Ultima version publicada en GitHub. Devuelve array con version, zip y
 * notas, o null si no hay nada nuevo o no se pudo consultar.
 */
function vempra_ultima_version( $forzar = false ) {
	$clave = 'vempra_core_release';
	$dato  = $forzar ? false : get_transient( $clave );
	if ( is_array( $dato ) ) { return $dato ?: null; }

	$r = wp_remote_get(
		'https://api.github.com/repos/' . vempra_repo_github() . '/releases/latest',
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'vempra-core/' . VEMPRA_CORE_VERSION,
			),
		)
	);

	$dato = array();
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$j       = json_decode( wp_remote_retrieve_body( $r ), true );
		$version = isset( $j['tag_name'] ) ? ltrim( (string) $j['tag_name'], 'vV' ) : '';

		$zip = '';
		if ( ! empty( $j['assets'] ) && is_array( $j['assets'] ) ) {
			foreach ( $j['assets'] as $a ) {
				if ( ! empty( $a['browser_download_url'] ) && preg_match( '/\.zip$/i', $a['name'] ) ) {
					$zip = $a['browser_download_url'];
					break;
				}
			}
		}
		if ( '' === $zip && ! empty( $j['zipball_url'] ) ) { $zip = $j['zipball_url']; }

		if ( $version && $zip && preg_match( '/^\d+\.\d+(\.\d+)?$/', $version ) ) {
			$dato = array(
				'version' => $version,
				'zip'     => $zip,
				'notas'   => isset( $j['body'] ) ? (string) $j['body'] : '',
				'url'     => isset( $j['html_url'] ) ? (string) $j['html_url'] : '',
			);
		}
	}

	// Si fallo, se guarda vacio igual: asi no se martilla la API de GitHub en
	// cada carga del panel. En 12 horas se vuelve a intentar.
	set_transient( $clave, $dato, 12 * HOUR_IN_SECONDS );
	return $dato ?: null;
}

add_filter( 'pre_set_site_transient_update_plugins', function ( $t ) {
	if ( ! is_object( $t ) ) { return $t; }

	$u = vempra_ultima_version();
	if ( ! $u || ! version_compare( $u['version'], VEMPRA_CORE_VERSION, '>' ) ) { return $t; }

	$base = vempra_plugin_base();
	$t->response[ $base ] = (object) array(
		'slug'        => 'vempra-core',
		'plugin'      => $base,
		'new_version' => $u['version'],
		'url'         => 'https://github.com/' . vempra_repo_github(),
		'package'     => $u['zip'],
		'icons'       => array(),
		'banners'     => array(),
		'tested'      => get_bloginfo( 'version' ),
	);
	return $t;
} );

/**
 * La ventanita "Ver detalles de la version" del panel.
 */
add_filter( 'plugins_api', function ( $res, $accion, $args ) {
	if ( 'plugin_information' !== $accion || empty( $args->slug ) || 'vempra-core' !== $args->slug ) {
		return $res;
	}
	$u = vempra_ultima_version();
	return (object) array(
		'name'          => 'Vempra Core',
		'slug'          => 'vempra-core',
		'version'       => $u ? $u['version'] : VEMPRA_CORE_VERSION,
		'author'        => 'Vempra',
		'homepage'      => 'https://github.com/' . vempra_repo_github(),
		'download_link' => $u ? $u['zip'] : '',
		'sections'      => array(
			'description' => 'La tienda de Vempra en codigo: precios, textos, medicion y ficha optimizada.',
			'changelog'   => $u ? wpautop( esc_html( $u['notas'] ) ) : '',
		),
	);
}, 10, 3 );

/**
 * Si el ZIP llega con la carpeta con otro nombre (por ejemplo el zipball de
 * GitHub, "feedeterra-vempra-core-abc123"), se renombra a vempra-core para
 * que WordPress actualice en el lugar y no instale una copia al lado.
 */
add_filter( 'upgrader_source_selection', function ( $origen, $remoto, $upgrader, $extra ) {
	if ( empty( $extra['plugin'] ) || vempra_plugin_base() !== $extra['plugin'] ) { return $origen; }

	global $wp_filesystem;
	$deseado = trailingslashit( dirname( untrailingslashit( $origen ) ) ) . 'vempra-core/';
	if ( untrailingslashit( $origen ) === untrailingslashit( $deseado ) ) { return $origen; }
	if ( $wp_filesystem && $wp_filesystem->move( $origen, $deseado, true ) ) { return $deseado; }
	return $origen;
}, 10, 4 );

// Al terminar de actualizar se olvida el dato guardado, para que el panel
// no siga anunciando la version que se acaba de instalar.
add_action( 'upgrader_process_complete', function ( $upgrader, $opciones ) {
	if ( isset( $opciones['type'] ) && 'plugin' === $opciones['type'] ) {
		delete_transient( 'vempra_core_release' );
	}
}, 10, 2 );

// Un enlace "Buscar actualizacion" en la fila del plugin, para no esperar
// las 12 horas cuando se sabe que acaba de salir una version.
add_filter( 'plugin_action_links_' . plugin_basename( VEMPRA_CORE_DIR . 'vempra-core.php' ), function ( $enlaces ) {
	$url = wp_nonce_url( admin_url( 'plugins.php?vempra_buscar=1' ), 'vempra_buscar' );
	$enlaces[] = '<a href="' . esc_url( $url ) . '">Buscar actualización</a>';
	return $enlaces;
} );

add_action( 'admin_init', function () {
	if ( empty( $_GET['vempra_buscar'] ) || ! current_user_can( 'update_plugins' ) ) { return; }
	check_admin_referer( 'vempra_buscar' );
	delete_transient( 'vempra_core_release' );
	delete_site_transient( 'update_plugins' );
	wp_safe_redirect( admin_url( 'plugins.php' ) );
	exit;
} );
