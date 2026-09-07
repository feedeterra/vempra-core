<?php
/**
 * FICHA DE TOUR: datos estructurados y letra chica bajo el precio.
 *
 * El theme grandtour imprime su propio bloque JSON-LD de Product al final de
 * la ficha, y lo imprime mal. Tal como sale hoy de produccion:
 *
 *   "aggregateRating": { "ratingValue": "0", "reviewCount": "0" }
 *   "priceCurrency": "$"
 *   "itemCondition": "https://schema.org/UsedCondition"
 *   "seller": { "name": "" }
 *   "brand":  { "name": "<el nombre del tour otra vez>" }
 *   "sku":    "483"   <- el ID de la pagina, no el SKU del producto
 *
 * Cuatro de esas seis lineas son errores que Google castiga:
 *
 *   - Una valoracion de 0 sobre 0 resenas es marcado invalido. Google puede
 *     descartar el resultado enriquecido ENTERO por eso, o sea que la ficha
 *     se queda sin estrellas y sin precio en el buscador.
 *   - "$" no es una moneda. La moneda es ARS. Sin eso no muestra el precio.
 *   - UsedCondition le esta diciendo a Google que el tour es usado.
 *   - El vendedor vacio y la marca con el nombre del tour no aportan nada.
 *
 * El theme no ofrece ningun filtro para eso: el bloque esta escrito a mano en
 * su plantilla. Asi que se intercepta la salida de la pagina y se reemplaza
 * el bloque entero por uno correcto, armado con los datos reales del producto
 * de WooCommerce.
 *
 * SOBRE LA VALORACION: aca NO se inventa ninguna. El sitio no tiene ni una
 * sola resena cargada en WordPress, asi que el bloque sale sin
 * aggregateRating. Un Product sin valoracion es perfectamente valido; uno con
 * valoracion en cero, no. Poner el "5.0 con +150 resenas" que la pagina le
 * muestra al visitante seria mentirle a Google: esas resenas son de Google y
 * TripAdvisor y son del negocio, no de cada tour, y marcarlas como propias de
 * cada producto es justo lo que Google sanciona. El dia que haya resenas de
 * verdad en WooCommerce, esta funcion las publica sola.
 *
 * De paso, ya que la salida esta interceptada, se arreglan dos cosas mas de
 * la misma ficha:
 *
 *   - El precio de cabecera sale "$95,000", con la coma de los miles en
 *     ingles. En el resto del sitio es "$95.000".
 *   - Debajo del precio no habia ninguna referencia a las cuotas ni al
 *     tamano del grupo, dos cosas que el visitante pregunta siempre.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Cuantas cuotas sin interes se anuncian.
 *
 * Es el mismo numero que ya dicen la portada, la tienda y las tarjetas del
 * catalogo, escrito una sola vez. Si Mercado Pago cambia la promocion, se
 * cambia aca o desde afuera con el filtro y queda igual en todo el sitio.
 */
function vempra_cuotas_sin_interes() {
	return (int) apply_filters( 'vempra_cuotas_sin_interes', 3 );
}

/**
 * Cupo maximo de personas por salida, leido del producto reservable.
 *
 * Devuelve 0 si el producto no lo tiene cargado, y en ese caso no se muestra
 * nada. Es el maximo del grupo, no los lugares que quedan libres: ese otro
 * numero depende de la fecha que el visitante todavia no eligio.
 */
function vempra_cupo_por_salida( $producto ) {

	if ( ! $producto ) { return 0; }

	$cupo = (int) get_post_meta( $producto->get_id(), '_wc_booking_max_persons', true );
	if ( $cupo <= 0 ) {
		$cupo = (int) get_post_meta( $producto->get_id(), '_bkap_max_person', true );
	}

	return $cupo > 0 ? $cupo : 0;
}

/**
 * El bloque JSON-LD que reemplaza al del theme.
 *
 * Devuelve el <script> completo, o cadena vacia si no hay producto ni precio
 * de donde sacar los datos: en ese caso se deja el del theme como estaba.
 */
function vempra_schema_de_tour( $tour_id ) {

	$producto = vempra_producto_de_tour( $tour_id );
	$precio   = vempra_precio_de_tour( $tour_id );

	if ( ! $producto || $precio <= 0 ) { return ''; }

	$nombre = wp_strip_all_tags( get_the_title( $tour_id ) );

	$descripcion = (string) get_post_meta( $tour_id, '_yoast_wpseo_metadesc', true );
	if ( '' === $descripcion ) {
		$descripcion = wp_strip_all_tags( get_the_excerpt( $tour_id ) );
	}

	$imagenes = array();
	$portada  = get_the_post_thumbnail_url( $tour_id, 'full' );
	if ( $portada ) { $imagenes[] = $portada; }

	$moneda = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'ARS';

	$datos = array(
		'@context'    => 'https://schema.org/',
		'@type'       => 'Product',
		'name'        => $nombre,
		'url'         => get_permalink( $tour_id ),
		'brand'       => array(
			'@type' => 'Brand',
			'name'  => 'Vempra',
		),
	);

	$sku = $producto->get_sku();
	if ( $sku ) { $datos['sku'] = $sku; }

	if ( $imagenes )     { $datos['image'] = $imagenes; }
	if ( $descripcion )  { $datos['description'] = $descripcion; }

	// La valoracion solo sale si hay resenas de verdad en WooCommerce.
	$resenas = (int) $producto->get_review_count();
	$puntaje = (float) $producto->get_average_rating();

	if ( $resenas > 0 && $puntaje > 0 ) {
		$datos['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => (string) round( $puntaje, 1 ),
			'reviewCount' => (string) $resenas,
		);
	}

	$datos['offers'] = array(
		'@type'           => 'Offer',
		'url'             => get_permalink( $tour_id ),
		'price'           => (string) round( $precio ),
		'priceCurrency'   => $moneda,
		'availability'    => 'https://schema.org/InStock',
		'itemCondition'   => 'https://schema.org/NewCondition',
		'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
		'seller'          => array(
			'@type' => 'Organization',
			'name'  => 'Vempra',
		),
	);

	$datos = apply_filters( 'vempra_schema_de_tour', $datos, $tour_id, $producto );

	$json = wp_json_encode( $datos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	if ( ! $json ) { return ''; }

	return '<script type="application/ld+json" id="vempra-schema">' . $json . '</script>';
}

/**
 * La letra chica que va debajo del precio: cuotas y cupo del grupo.
 */
function vempra_letra_chica_de_tour( $tour_id ) {

	$producto = vempra_producto_de_tour( $tour_id );
	$precio   = vempra_precio_de_tour( $tour_id );
	$cuotas   = vempra_cuotas_sin_interes();

	$lineas = array();

	if ( $precio > 0 && $cuotas > 1 ) {
		$lineas[] = sprintf(
			'%d cuotas sin interés de $%s',
			$cuotas,
			number_format( $precio / $cuotas, 0, ',', '.' )
		);
	}

	$cupo = vempra_cupo_por_salida( $producto );
	if ( $cupo > 0 ) {
		$lineas[] = sprintf( 'Hasta %d personas por salida', $cupo );
	}

	if ( ! $lineas ) { return ''; }

	$html = '<div class="vempra-precio-detalle">';
	foreach ( $lineas as $linea ) {
		$html .= '<span>' . esc_html( $linea ) . '</span>';
	}
	$html .= '</div>';

	return $html;
}

/**
 * Pasa "$95,000" a "$95.000" dentro del bloque de precio de la ficha.
 *
 * El theme escribe el numero con la coma de los miles en ingles. Se toca
 * solamente lo que hay adentro de .single_tour_price, para no andar
 * reemplazando comas por el resto de la pagina.
 */
function vempra_precio_a_la_argentina( $html ) {

	return preg_replace_callback(
		'#(<div class="single_tour_price">)(.*?)(</div>)#s',
		function ( $m ) {
			$adentro = preg_replace_callback(
				'/(\d{1,3}(?:,\d{3})+)/',
				function ( $n ) { return str_replace( ',', '.', $n[1] ); },
				$m[2]
			);
			return $m[1] . $adentro . $m[3];
		},
		$html
	);
}

/**
 * El reemplazo completo sobre el HTML ya armado de la ficha.
 */
function vempra_arreglar_ficha( $html, $tour_id ) {

	if ( ! is_string( $html ) || '' === $html ) { return $html; }

	// 1) El JSON-LD del theme por el nuestro.
	$nuestro = vempra_schema_de_tour( $tour_id );

	if ( $nuestro ) {
		$html = preg_replace_callback(
			'#<script[^>]*type=[\'"]application/ld\+json[\'"][^>]*>(.*?)</script>#is',
			function ( $m ) use ( &$nuestro ) {
				// Solo el bloque de Product, y solo el primero: el resto del
				// grafico (WebSite, Organization, BreadcrumbList) lo pone el
				// plugin de SEO y esta bien.
				if ( '' === $nuestro ) { return $m[0]; }
				if ( ! preg_match( '/"@type"\\s*:\\s*"Product"/', $m[1] ) ) { return $m[0]; }
				$reemplazo = $nuestro;
				$nuestro   = '';
				return $reemplazo;
			},
			$html
		);
	}

	// 2) El precio con el separador de miles argentino.
	$html = vempra_precio_a_la_argentina( $html );

	// 3) Las cuotas y el cupo, debajo de "por persona". El theme imprime ese
	//    bloque dos veces (cabecera y barra lateral) y las dos reciben lo
	//    mismo.
	$letra = vempra_letra_chica_de_tour( $tour_id );

	if ( $letra ) {
		$html = preg_replace(
			'#(<div class="single_tour_per_person">.*?</div>)#s',
			'$1' . str_replace( '$', '\\$', $letra ),
			$html
		);
	}

	return $html;
}

/**
 * Se engancha en la salida de la pagina solo en las fichas de tour.
 *
 * El theme no da ningun filtro para su JSON-LD ni para su bloque de precio,
 * asi que no hay otra manera de tocarlos que leer el HTML ya armado. El
 * buffer se abre lo mas tarde posible y solo en las fichas: en el resto del
 * sitio no se abre nunca.
 */
add_action( 'template_redirect', function () {

	if ( is_admin() || ! is_singular( VEMPRA_TOUR_CPT ) ) { return; }
	if ( ! apply_filters( 'vempra_arreglar_fichas', true ) ) { return; }

	$tour_id = get_queried_object_id();
	if ( ! $tour_id ) { return; }

	ob_start( function ( $html ) use ( $tour_id ) {
		// Si algo falla aca adentro, PHP se come el error y la pagina sale en
		// blanco. Por eso el try: ante cualquier problema devolvemos el HTML
		// original y la ficha queda como estaba.
		try {
			return vempra_arreglar_ficha( $html, $tour_id );
		} catch ( Throwable $e ) {
			return $html;
		}
	} );

}, 99 );
