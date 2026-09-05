<?php
/**
 * PRECIO UNICO
 *
 * El problema que resuelve: el precio de cada tour estaba escrito en tres
 * lugares (el producto de WooCommerce, el campo tour_price de la pagina del
 * tour, y un array dentro del JavaScript). Se desincronizaban solos.
 *
 * A partir de aca manda el producto de WooCommerce y nada mas. El campo
 * tour_price sigue existiendo en la base de datos pero ya no se lee: cuando
 * alguien lo pide, devolvemos el precio real del producto.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function vempra_precio_unico_activo() {
	return 'no' !== get_option( 'vempra_precio_unico', 'si' );
}

/**
 * Producto de WooCommerce vinculado a una pagina de tour.
 */
function vempra_producto_de_tour( $tour_id ) {
	$pid = get_post_meta( $tour_id, VEMPRA_META_PRODUCTO, true );
	if ( ! $pid ) { return null; }
	$producto = function_exists( 'wc_get_product' ) ? wc_get_product( (int) $pid ) : null;
	return $producto ?: null;
}

/**
 * Costo de la tarifa "Adulto" de un producto reservable.
 *
 * Siete de los dieciocho tours no tienen precio en el campo _price: son
 * productos de WooCommerce Bookings con costo base 0 y el precio real
 * cargado en los tipos de pasajero. El precio que ve el visitante en la
 * cabecera de la ficha ya sale de aca (Bookings muestra el costo de la
 * tarifa Adulto), asi que leerlo no inventa nada: reproduce el numero que
 * la pagina ya muestra.
 *
 * Antes se buscaba la tarifa por el nombre "Adulto" y nunca coincidia: en
 * este sitio las tarifas se llaman "Mayor de 13", "Entre 5 y 12" y "Menor
 * de 5", asi que la funcion devolvia 0 en los dieciocho tours y el fallback
 * no servia para nada. Ahora se toma la tarifa MAS CARA, que es siempre la
 * de adulto (55.000 contra 27.500 del menor y 5.000 del bebe) y que no se
 * rompe si manana renombran las etiquetas.
 *
 * Devuelve float, o 0.0 si el producto no tiene ninguna tarifa con costo.
 */
function vempra_costo_adulto( $producto ) {

	if ( ! $producto || ! method_exists( $producto, 'get_person_types' ) ) { return 0.0; }

	// get_person_types() consulta la base cada vez, y el filtro de tour_price
	// se dispara varias veces por pagina. Lo resolvemos una sola vez por
	// producto y por request.
	static $visto = array();

	$id = $producto->get_id();
	if ( isset( $visto[ $id ] ) ) { return $visto[ $id ]; }

	$visto[ $id ] = 0.0;

	foreach ( (array) $producto->get_person_types() as $tipo ) {

		if ( ! is_object( $tipo ) || ! method_exists( $tipo, 'get_cost' ) ) { continue; }

		$costo = (float) $tipo->get_cost();
		if ( $costo > $visto[ $id ] ) { $visto[ $id ] = $costo; }
	}

	return $visto[ $id ];
}

/**
 * Precio de un producto: el de WooCommerce, y si no tiene, la tarifa Adulto.
 *
 * En los dos casos manda WooCommerce. El fallback existe porque los tours
 * reservables guardan el precio en la tarifa Adulto en lugar del campo
 * _price, y sin el se colaba el valor viejo de tour_price en las tarjetas
 * (el Rafting aparecia a 130.000 cuando vale 99.000).
 *
 * Devuelve float, o 0.0 si el producto no tiene precio por ningun lado.
 */
function vempra_precio_de_producto( $producto ) {

	if ( ! $producto ) { return 0.0; }

	$precio = (float) $producto->get_price();
	if ( $precio > 0 ) { return $precio; }

	return vempra_costo_adulto( $producto );
}

/**
 * Tipos de pasajero de un producto reservable, como nombre => costo.
 * Solo informativo: no participa del precio que se muestra en la web.
 */
function vempra_tipos_de_pasajero( $producto ) {

	if ( ! $producto || ! method_exists( $producto, 'get_person_types' ) ) { return array(); }

	$tipos = array();
	foreach ( (array) $producto->get_person_types() as $tipo ) {
		if ( ! is_object( $tipo ) || ! method_exists( $tipo, 'get_cost' ) ) { continue; }
		$nombre = method_exists( $tipo, 'get_name' ) ? (string) $tipo->get_name() : 'sin nombre';
		$tipos[ $nombre ] = (float) $tipo->get_cost();
	}

	return $tipos;
}

/**
 * Precio a mostrar para una pagina de tour.
 */
function vempra_precio_de_tour( $tour_id ) {
	return vempra_precio_de_producto( vempra_producto_de_tour( $tour_id ) );
}

/**
 * Intercepta las lecturas de tour_price y devuelve el precio real.
 *
 * El guard evita recursion: dentro del filtro volvemos a pedir metadatos
 * (tour_booking_product), lo que dispararia el filtro de nuevo.
 */
add_filter( 'get_post_metadata', function ( $valor, $object_id, $meta_key, $single ) {

	static $adentro = false;

	if ( $adentro || VEMPRA_META_PRECIO !== $meta_key ) { return $valor; }
	if ( ! vempra_precio_unico_activo() ) { return $valor; }
	if ( VEMPRA_TOUR_CPT !== get_post_type( $object_id ) ) { return $valor; }

	$adentro = true;
	$precio  = vempra_precio_de_tour( $object_id );
	$adentro = false;

	if ( $precio <= 0 ) { return $valor; }  // sin dato mejor, dejamos lo que habia

	$precio = (string) $precio;

	return $single ? $precio : array( $precio );

}, 10, 4 );

/**
 * Mapa slug => precio para el JavaScript del sitio.
 *
 * El array de tours que hoy vive dentro del JS tiene los precios escritos a
 * mano. Este mapa deja disponible el precio real para que ese JS lo use en
 * lugar de su copia. Se cachea 12 horas y se invalida al guardar cualquier
 * tour o producto.
 */
function vempra_mapa_precios() {

	$cache = get_transient( 'vempra_mapa_precios' );
	if ( false !== $cache ) { return $cache; }

	$mapa  = array();
	$tours = get_posts( array(
		'post_type'      => VEMPRA_TOUR_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $tours as $tour_id ) {
		$precio = vempra_precio_de_tour( $tour_id );
		if ( $precio > 0 ) {
			$mapa[ get_post_field( 'post_name', $tour_id ) ] = $precio;
		}
	}

	set_transient( 'vempra_mapa_precios', $mapa, 12 * HOUR_IN_SECONDS );
	return $mapa;
}

add_action( 'save_post', function ( $post_id ) {
	$tipo = get_post_type( $post_id );
	if ( VEMPRA_TOUR_CPT === $tipo || 'product' === $tipo ) {
		delete_transient( 'vempra_mapa_precios' );
	}
} );

/**
 * Invalidacion por metadato, que es lo que faltaba.
 *
 * Con solo save_post el mapa se quedaba viejo hasta doce horas: cambiar el
 * precio desde la API de WooCommerce, desde un import o desde cualquier
 * herramienta que escriba el metadato directo NO dispara save_post. Paso de
 * verdad: se subio el Tour Bodegas a 55.000 y la ficha siguio mostrando
 * 53.000 el resto del dia.
 *
 * Se miran los metadatos de precio del producto y el costo de los tipos de
 * pasajero, que es de donde sale el numero cuando el producto tiene _price
 * en cero.
 */
function vempra_invalidar_mapa_por_meta( $meta_id, $object_id, $meta_key ) {

	$claves = array( '_price', '_regular_price', '_sale_price', 'cost', 'block_cost', VEMPRA_META_PRECIO );

	if ( in_array( $meta_key, $claves, true ) ) {
		delete_transient( 'vempra_mapa_precios' );
	}
}

add_action( 'updated_post_meta', 'vempra_invalidar_mapa_por_meta', 10, 3 );
add_action( 'added_post_meta',   'vempra_invalidar_mapa_por_meta', 10, 3 );
add_action( 'deleted_post_meta', 'vempra_invalidar_mapa_por_meta', 10, 3 );

/**
 * Y por las dudas, cuando WooCommerce guarda un producto por su propia via
 * (la API REST y el panel pasan por aca aunque no siempre por save_post).
 */
add_action( 'woocommerce_update_product', function () {
	delete_transient( 'vempra_mapa_precios' );
} );
