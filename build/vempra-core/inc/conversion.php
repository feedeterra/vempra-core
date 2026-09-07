<?php
/**
 * CONVERSION: lo que empuja la reserva.
 *
 * Todo lo de este archivo salio de la auditoria de CRO de la ficha y del
 * carrito. Son piezas que no existian en el sitio y que se agregan sin tocar
 * el HTML que ya se ve: la barra sticky se dibuja en el pie, el bloque de
 * precio entra por el shortcode del formulario y los tours sugeridos se
 * cuelgan del contenido.
 *
 *   - Barra de reserva fija en celular, con precio y boton         (M-01)
 *   - Precio y estado del formulario visibles sin esperar al JS    (M-02)
 *   - Minimo de personas a la vista, antes del error de Bookings   (F-05)
 *   - "Combinalo con": tres tours sugeridos en cada ficha          (U-03)
 *   - Los mismos tres, en el carrito                               (U-02)
 *   - og:locale en es_AR y no en es_ES                             (M-07)
 *
 * Las tarjetas de los sugeridos son las mismas del catalogo
 * (vempra_catalogo_tarjeta), asi que se ven igual que en Tours en Mendoza y
 * no hay un segundo diseno que mantener.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ---------------------------------------------------------------------------
// U-03 y U-02: que tour combina con cual.
// ---------------------------------------------------------------------------

/**
 * Tour => los tres tours que mejor lo acompanan, en orden.
 *
 * Esta escrito a mano y no calculado por categoria porque lo que combina no
 * es lo parecido sino lo distinto: quien ya reservo un wine tour no quiere
 * otro wine tour, quiere la montana. La regla que se siguio:
 *
 *   - primero el complemento real (montana <-> vino <-> aventura),
 *   - despues un medio dia barato, que es la compra facil de sumar,
 *   - y nunca el mismo tour ni otro casi igual.
 *
 * Se puede cambiar desde afuera sin tocar el plugin:
 *
 *   add_filter( 'vempra_combina_con', function ( $mapa ) {
 *       $mapa[483] = array( 528, 535, 530 );
 *       return $mapa;
 *   } );
 */
function vempra_combina_con() {
	return (array) apply_filters( 'vempra_combina_con', array(
		483 => array( 528, 534, 530 ), // Alta Montana -> bodegas, cabalgata, Villavicencio
		527 => array( 483, 534, 530 ), // Wine Lujan   -> montana, cabalgata, Villavicencio
		526 => array( 483, 533, 530 ), // Wine Maipu   -> montana, cabalgata criolla, Villavicencio
		528 => array( 483, 533, 535 ), // Bodegas      -> montana, cabalgata, termas
		529 => array( 483, 526, 535 ), // Atuel        -> montana, wine tour, termas
		530 => array( 483, 528, 534 ), // Villavicencio-> montana, bodegas, cabalgata
		531 => array( 483, 538, 526 ), // Villavicencio 4x4 -> montana, rafting, wine tour
		534 => array( 528, 483, 535 ), // Cabalgata sunset -> bodegas, montana, termas
		532 => array( 528, 483, 530 ), // Cabalgata almuerzo
		533 => array( 528, 483, 530 ), // Cabalgata criolla
		535 => array( 483, 528, 534 ), // Termas Cacheuta
		536 => array( 483, 528, 534 ), // Combo termas
		537 => array( 483, 528, 535 ), // Full day aventura
		538 => array( 535, 483, 528 ), // Rafting
		523 => array( 483, 528, 535 ), // Las Lenas
		524 => array( 483, 528, 535 ), // Los Puquios
		525 => array( 483, 528, 535 ), // Penitentes
		754 => array( 483, 534, 530 ), // Wine tour con picnic
	) );
}

/**
 * Los datos de catalogo de los tours sugeridos para un tour dado.
 *
 * Devuelve como mucho $cuantos, salteando los que esten en $excluir, los que
 * no existan en el catalogo y los que no esten publicados. Si el mapa no
 * alcanza para llenar los tres lugares, completa con el orden del catalogo,
 * que ya esta ordenado por lo que mas se vende.
 */
function vempra_sugeridos( $tour_id, $excluir = array(), $cuantos = 3 ) {

	if ( ! function_exists( 'vempra_catalogo_tours' ) ) { return array(); }

	$catalogo = array();
	foreach ( vempra_catalogo_tours() as $t ) {
		if ( ! empty( $t['id'] ) ) { $catalogo[ (int) $t['id'] ] = $t; }
	}

	$excluir   = array_map( 'intval', (array) $excluir );
	$excluir[] = (int) $tour_id;

	$mapa   = vempra_combina_con();
	$orden  = isset( $mapa[ (int) $tour_id ] ) ? (array) $mapa[ (int) $tour_id ] : array();
	$orden  = array_merge( $orden, array_keys( $catalogo ) );

	$salida = array();
	foreach ( $orden as $id ) {
		$id = (int) $id;
		if ( in_array( $id, $excluir, true ) ) { continue; }
		if ( ! isset( $catalogo[ $id ] ) ) { continue; }
		if ( 'publish' !== get_post_status( $id ) ) { continue; }

		$excluir[] = $id;

		// En el bloque de sugeridos las tarjetas van todas del mismo tamano:
		// la variante grande del catalogo rompe la fila de tres.
		$t              = $catalogo[ $id ];
		$t['feature']   = false;
		$salida[]       = $t;

		if ( count( $salida ) >= (int) $cuantos ) { break; }
	}

	return $salida;
}

/**
 * El bloque entero de sugeridos, listo para imprimir. Cadena vacia si no
 * quedo ninguna tarjeta que mostrar.
 */
function vempra_bloque_sugeridos( $tarjetas, $titulo, $bajada = '' ) {

	if ( empty( $tarjetas ) || ! function_exists( 'vempra_catalogo_tarjeta' ) ) { return ''; }

	$cuerpo = '';
	foreach ( $tarjetas as $t ) {
		$cuerpo .= vempra_catalogo_tarjeta( $t );
	}
	if ( '' === $cuerpo ) { return ''; }

	$h  = '<section class="vempra-combina">';
	$h .= '<h2 class="vempra-combina-titulo">' . esc_html( $titulo ) . '</h2>';
	if ( '' !== $bajada ) {
		$h .= '<p class="vempra-combina-bajada">' . esc_html( $bajada ) . '</p>';
	}
	$h .= '<div class="vempra-shop-grid vempra-combina-grid">' . $cuerpo . '</div>';
	$h .= '</section>';

	return $h;
}

/**
 * U-03: "Combinalo con" al final de la ficha del tour.
 *
 * Va en the_content y no en un hook del theme porque la ficha es una entrada
 * del CPT tour: el unico lugar seguro para colgarse es su propio contenido.
 * Prioridad 25 para entrar despues de los filtros de precio del plugin.
 */
add_filter( 'the_content', function ( $html ) {

	if ( is_admin() || ! is_singular( VEMPRA_TOUR_CPT ) || ! in_the_loop() || ! is_main_query() ) {
		return $html;
	}
	if ( ! apply_filters( 'vempra_mostrar_sugeridos', true ) ) { return $html; }

	$tour_id = (int) get_the_ID();
	$bloque  = vempra_bloque_sugeridos(
		vempra_sugeridos( $tour_id ),
		'Combinalo con',
		'Los tours que mejor se arman con este. Se pueden reservar para el mismo viaje.'
	);

	return $html . $bloque;
}, 25 );

// ---------------------------------------------------------------------------
// F-05 y M-02: la cabecera del formulario de reserva.
// ---------------------------------------------------------------------------

/**
 * Minimo de personas del producto reservable. 1 si no tiene minimo cargado.
 *
 * Se lee del producto y no de una lista de IDs a mano: hoy solo los dos wine
 * tours de bodega tienen minimo de 2, pero eso se cambia desde el escritorio
 * de WooCommerce y ahi no hay manera de acordarse de tocar el plugin.
 */
function vempra_min_personas( $product_id ) {
	$min = (int) get_post_meta( (int) $product_id, '_wc_booking_min_persons', true );
	return $min > 1 ? $min : 1;
}

/**
 * Lo que se ve arriba del formulario de reserva antes de que arranque el
 * JavaScript de Bookings.
 *
 * El formulario de Bookings sale del servidor con style="display:none" y
 * recien aparece cuando su JavaScript termino de armar el calendario. Hasta
 * ese momento, donde tendria que estar el boton de reservar no hay nada: ni
 * un boton, ni un aviso, ni una explicacion. En celular eso son varios
 * segundos de sidebar en blanco.
 *
 * Este bloque lo escribe el servidor, asi que esta dibujado desde el primer
 * cuadro:
 *
 *   - el minimo de personas cuando el tour lo tiene, antes de que Bookings
 *     lo diga en forma de error,
 *   - y un renglon de estado que avisa que el calendario esta cargando. El
 *     JavaScript lo saca cuando el formulario aparece, y si a los ocho
 *     segundos el formulario sigue sin aparecer lo convierte en un enlace a
 *     WhatsApp: la reserva no se pierde por un JavaScript que no cargo.
 *
 * El precio no se repite aca: el theme ya lo imprime del lado del servidor
 * justo arriba, en single_tour_header_price.
 */
function vempra_reserva_cabecera( $product_id ) {

	$product_id = (int) $product_id;
	if ( ! $product_id ) { return ''; }

	$min = vempra_min_personas( $product_id );

	$h = '<div class="vempra-reserva-cabecera" data-min-personas="' . esc_attr( $min ) . '">';

	if ( $min > 1 ) {
		$h .= '<p class="vempra-reserva-minimo">';
		$h .= '<b>Mínimo ' . esc_html( $min ) . ' personas por reserva.</b> ';
		$h .= 'Pueden ser adultos o menores: cuenta el total del grupo.';
		$h .= '</p>';
	}

	$wa   = function_exists( 'vempra_whatsapp' ) ? vempra_whatsapp() : '';
	$link = $wa ? 'https://wa.me/' . rawurlencode( $wa ) : '';

	$h .= '<p class="vempra-reserva-estado" role="status" data-wa="' . esc_attr( $link ) . '">';
	$h .= '<span class="vempra-reserva-spin" aria-hidden="true"></span>';
	$h .= 'Preparando el calendario…';
	$h .= '</p>';

	$h .= '</div>';

	return $h;
}

// ---------------------------------------------------------------------------
// M-01: barra de reserva fija en celular.
//
// Se dibuja en el pie y arranca visible: es la unica manera de tener el
// precio y el boton arriba del pliegue sin tocar la portada de la ficha, que
// es lo que no se quiere mover. El JavaScript la esconde mientras el
// formulario de reserva esta a la vista, para no tapar el propio calendario.
// ---------------------------------------------------------------------------
add_action( 'wp_footer', function () {

	if ( ! is_singular( VEMPRA_TOUR_CPT ) ) { return; }
	if ( ! apply_filters( 'vempra_barra_sticky', true ) ) { return; }

	$tour_id = (int) get_queried_object_id();
	$precio  = function_exists( 'vempra_precio_de_tour' ) ? (float) vempra_precio_de_tour( $tour_id ) : 0;

	echo '<div class="vempra-sticky" id="vempra-sticky">';
	echo '<div class="vempra-sticky-datos">';
	if ( $precio > 0 && function_exists( 'vempra_precio_en_pesos' ) ) {
		echo '<span class="vempra-sticky-monto">' . esc_html( vempra_precio_en_pesos( $precio ) ) . '</span>';
		echo '<span class="vempra-sticky-unidad">por persona</span>';
	} else {
		echo '<span class="vempra-sticky-unidad">Consultá disponibilidad</span>';
	}
	echo '</div>';
	echo '<button type="button" class="vempra-sticky-cta">Reservar</button>';
	echo '</div>';

}, 20 );

// ---------------------------------------------------------------------------
// M-07: og:locale.
//
// El sitio esta en es-AR (asi sale el <html lang>), pero Yoast publica
// es_ES porque es el locale que WordPress tiene por defecto para el espanol.
// Facebook usa ese dato para decidir en que idioma muestra la vista previa
// del enlace, y de paso es la senal mas barata de que el negocio es argentino.
// ---------------------------------------------------------------------------
add_filter( 'wpseo_og_locale', function () {
	return apply_filters( 'vempra_og_locale', 'es_AR' );
}, 20 );

// ---------------------------------------------------------------------------
// U-02: venta cruzada en el carrito.
//
// El carrito de este sitio es el de bloques, asi que
// woocommerce_cart_collaterals no se dispara nunca y la venta cruzada nativa
// de WooCommerce no se dibuja (por eso estaba desactivada en inc/tienda.php:
// ocupaba lugar y no mostraba nada). Se cuelga del contenido de la pagina,
// que es lo unico que corre igual en el carrito de bloques.
//
// Las tarjetas llevan al tour y no a "agregar al carrito": son productos
// reservables, sin fecha elegida no se pueden agregar.
// ---------------------------------------------------------------------------
add_filter( 'the_content', function ( $html ) {

	if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() ) { return $html; }
	if ( ! apply_filters( 'vempra_sugeridos_carrito', true ) ) { return $html; }
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) { return $html; }

	// De cada producto del carrito, su ficha de tour: son los que hay que
	// sacar de las sugerencias.
	$mapa     = function_exists( 'vempra_product_to_tour_map' ) ? vempra_product_to_tour_map() : array();
	$en_carro = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$pid = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		if ( $pid && isset( $mapa[ $pid ] ) ) { $en_carro[] = (int) $mapa[ $pid ]; }
	}

	// El punto de partida es el ultimo tour agregado: es el que el visitante
	// tiene fresco en la cabeza.
	$base = $en_carro ? (int) end( $en_carro ) : 0;

	$bloque = vempra_bloque_sugeridos(
		vempra_sugeridos( $base, $en_carro ),
		'Sumá otro tour a tu viaje',
		'Se reservan por separado y cada uno con su fecha. Te esperamos en el mismo hotel.'
	);

	return $html . $bloque;
}, 25 );
