<?php
/**
 * CACHE
 *
 * LiteSpeed guarda una copia de cada respuesta y se la sirve despues a
 * cualquier visitante. Para el HTML de una ficha eso esta perfecto. Para el
 * carrito es un desastre: el 7 de septiembre de 2026 una peticion sin
 * ninguna cookie a /wp-json/wc/store/v1/cart devolvia el carrito de otro
 * visitante -- el tour y la fecha que habia elegido -- con la cabecera
 * "x-litespeed-cache-control: public,max-age=604800". Una semana.
 *
 * El carrito de bloques se llena justamente desde ese endpoint, asi que el
 * visitante podia abrir el carrito y encontrar adentro un tour que nunca
 * puso, o ver el contador con un numero que no era el suyo. WooCommerce ya
 * manda "cache-control: no-store" en esa respuesta y LiteSpeed lo ignora,
 * asi que hay que decirselo con su propia API.
 *
 * Se arregla desde aca y no desde el panel de LiteSpeed a proposito: la
 * lista de exclusiones del panel no esta versionada. Una restauracion del
 * backup, una importacion de ajustes o una mano distraida la borra y la
 * fuga vuelve sin que nadie se entere. Aca viaja con el plugin.
 *
 * Se apaga con el filtro vempra_no_cachear_api.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Los pedazos de URL que nunca se pueden compartir entre visitantes.
 *
 * Va a lo ancho -- toda la REST API, no solo el carrito -- porque cachear
 * REST en esta tienda no ahorra nada medible y ya provoco una fuga real. Si
 * alguna vez hace falta afinarlo, el filtro deja cambiar la lista.
 */
function vempra_rutas_sin_cache() {
	return apply_filters( 'vempra_rutas_sin_cache', array(
		'/wp-json/',    // la REST API por su ruta linda
		'rest_route=',  // y por la fea, que es la que queda si no hay permalinks
		'wc-ajax=',     // los fragmentos del carrito y el checkout viejo
	) );
}

/**
 * Si la URL del pedido cae en alguna de esas rutas.
 */
function vempra_pedido_sin_cache() {

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( '' === $uri ) { return false; }

	foreach ( vempra_rutas_sin_cache() as $aguja ) {
		if ( '' !== $aguja && false !== strpos( $uri, $aguja ) ) { return true; }
	}

	return false;
}

/**
 * Le avisa a LiteSpeed que esta respuesta es de una sola persona.
 *
 * litespeed_control_set_nocache es la API publica del plugin. Si LiteSpeed
 * no esta instalado el do_action no hace nada, asi que esto es inofensivo
 * en cualquier hosting.
 */
function vempra_marcar_sin_cache( $motivo ) {

	do_action( 'litespeed_control_set_nocache', 'Vempra Core: ' . $motivo );

	if ( ! headers_sent() ) {
		nocache_headers();
		// Para poder verificarlo desde afuera con un curl, sin entrar al panel.
		header( 'X-Vempra-Nocache: ' . $motivo );
	}
}

/**
 * La REST API y los AJAX de WooCommerce. Corre en init porque para cuando
 * llega el pedido a rest_api_init LiteSpeed ya decidio, y porque asi el
 * mismo chequeo agarra tambien admin-ajax y ?rest_route=.
 */
add_action( 'init', function () {

	if ( ! apply_filters( 'vempra_no_cachear_api', true ) ) { return; }
	if ( ! vempra_pedido_sin_cache() ) { return; }

	vempra_marcar_sin_cache( 'api' );

}, 20 );

/**
 * Y las paginas que muestran datos del visitante. Hoy LiteSpeed ya las trata
 * bien porque WooCommerce le pone sus propias reglas, pero eso depende de un
 * ajuste del panel: si alguien lo toca, esto las sigue cubriendo.
 */
add_action( 'wp', function () {

	if ( ! apply_filters( 'vempra_no_cachear_api', true ) ) { return; }
	if ( is_admin() || ! function_exists( 'is_cart' ) ) { return; }

	if ( is_cart() || is_checkout() || is_account_page() ) {
		vempra_marcar_sin_cache( 'carrito' );
	}
} );
