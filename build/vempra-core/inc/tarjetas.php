<?php
/**
 * TARJETAS DE TOUR
 *
 * Segunda mitad del precio unico. inc/precios.php ya hace que la ficha de
 * cada tour muestre el precio de WooCommerce; lo que faltaba eran las
 * tarjetas de la pagina "Tours en Mendoza", que estan escritas a mano en el
 * editor con el precio dentro del HTML:
 *
 *   <a href="/tour/alta-montana/" ... data-price="95000">
 *       ... <span class="vempra-shop-card-price-value">$95.000</span>
 *
 * Ese numero no lo lee nadie de WooCommerce: hay que editarlo a mano cada
 * vez que cambia una tarifa. Paso de verdad y no una vez: la pagina llego a
 * tener diez precios viejos al mismo tiempo, con la Cabalgata al Atardecer
 * anunciada a 212.000 cuando se cobraba 165.000, y el Wine Tour con Picnic
 * directamente no figuraba.
 *
 * A partir de aca el HTML guardado deja de mandar. Al dibujar la pagina se
 * reemplazan el data-price y el precio visible de cada tarjeta por el precio
 * real del producto de WooCommerce, buscado por el slug del enlace. El texto
 * del editor queda como esta y no hace falta tocarlo nunca mas.
 *
 * Se hace en PHP y no en JavaScript a proposito: asi el precio correcto ya
 * viaja en el HTML. Si se corrigiera despues en el navegador, el visitante
 * veria el precio viejo un instante, y Google y las redes —que no ejecutan
 * el JavaScript al leer la pagina— seguirian indexando el numero viejo.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Precio como lo escribe el sitio: $95.000, sin centavos.
 *
 * No se usa wc_price() porque devuelve HTML con sus propias etiquetas y aca
 * el numero va dentro de un span que ya tiene su clase.
 */
function vempra_precio_en_pesos( $precio ) {
	return '$' . number_format( (float) $precio, 0, ',', '.' );
}

/**
 * Slug del tour dentro de un enlace /tour/<slug>/.
 *
 * Devuelve cadena vacia si el href no es una ficha de tour (el pie y la
 * cabecera tienen enlaces a otras paginas dentro del mismo contenido).
 */
function vempra_slug_de_enlace( $href ) {
	$ruta = wp_parse_url( $href, PHP_URL_PATH );
	if ( ! $ruta ) { return ''; }

	return preg_match( '#/tour/([^/]+)/?$#', $ruta, $m ) ? $m[1] : '';
}

/**
 * Reemplaza el precio de una tarjeta por el real. Devuelve la tarjeta tal
 * cual si no se puede afirmar un precio mejor que el que ya tiene.
 */
function vempra_tarjeta_con_precio_real( $tarjeta, $mapa ) {

	if ( ! preg_match( '#href="([^"]+)"#', $tarjeta, $m ) ) { return $tarjeta; }

	$slug = vempra_slug_de_enlace( $m[1] );
	if ( '' === $slug || empty( $mapa[ $slug ] ) ) { return $tarjeta; }

	$precio = (float) $mapa[ $slug ];
	if ( $precio <= 0 ) { return $tarjeta; }

	$tarjeta = preg_replace(
		'#(data-price=")[^"]*(")#',
		'${1}' . (int) round( $precio ) . '${2}',
		$tarjeta
	);

	// Con preg_replace() el precio se pierde: el reemplazo se escribe como
	// texto y PHP lee "$95.000" como la retrorreferencia $95, que no existe,
	// asi que la borra y en la web queda ".000". Por eso va un callback, que
	// devuelve el texto tal cual sin interpretar el signo peso.
	$tarjeta = preg_replace_callback(
		'#(<span class="vempra-shop-card-price-value">)[^<]*(</span>)#',
		function ( $m ) use ( $precio ) {
			return $m[1] . vempra_precio_en_pesos( $precio ) . $m[2];
		},
		$tarjeta
	);

	return $tarjeta;
}

/**
 * Cantidad de experiencias que anuncia la cabecera de la pagina.
 *
 * Tambien estaba escrita a mano: decia "+17 experiencias disponibles" con
 * dieciocho tours publicados. Se toma la cuenta real, la misma que usa el
 * pie del sitio.
 */
function vempra_cabecera_con_cuenta_real( $html ) {

	if ( ! function_exists( 'vempra_cuantos_tours' ) ) { return $html; }

	$tours = vempra_cuantos_tours();
	if ( $tours < 1 ) { return $html; }

	return preg_replace(
		'#(class="vempra-shop-hero-eyebrow">)\+?\d+( experiencias)#u',
		'${1}+' . $tours . '${2}',
		$html
	);
}

/**
 * El filtro. Solo mira contenidos que tengan tarjetas, que es una sola
 * pagina del sitio: en el resto sale por el primer if sin hacer nada.
 */
add_filter( 'the_content', function ( $html ) {

	if ( ! is_string( $html ) || false === strpos( $html, 'vempra-shop-card' ) ) {
		return $html;
	}

	// El catalogo generado por [vempra_tours] (inc/catalogo.php) ya trae el
	// precio real: no hay nada que corregir.
	if ( false !== strpos( $html, 'data-vempra="catalogo"' ) ) {
		return $html;
	}

	if ( function_exists( 'vempra_precio_unico_activo' ) && ! vempra_precio_unico_activo() ) {
		return $html;
	}

	$mapa = function_exists( 'vempra_mapa_precios' ) ? vempra_mapa_precios() : array();
	if ( empty( $mapa ) ) { return $html; }

	$html = preg_replace_callback(
		'#<a\b[^>]*\bclass="[^"]*vempra-shop-card\b[^"]*"[^>]*>.*?</a>#is',
		function ( $m ) use ( $mapa ) {
			return vempra_tarjeta_con_precio_real( $m[0], $mapa );
		},
		$html
	);

	return vempra_cabecera_con_cuenta_real( $html );

}, 20 );
