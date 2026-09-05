<?php
/**
 * FRONTEND
 *
 * Arreglos chicos que no entran en ningun otro archivo y que hoy no se
 * pueden hacer donde corresponderia:
 *
 *   - El zoom: el theme escribe maximum-scale=1 en el viewport y eso le
 *     saca al visitante la posibilidad de agrandar con los dedos. La
 *     etiqueta la imprime header.php, que no es filtrable desde un plugin.
 *   - El alt del logo: el theme lo imprime sin alt.
 *   - El "Ver los N tours" del pie: lo escribe un snippet con el numero a
 *     mano. Hasta que ese snippet este dentro del plugin, aca se corrige
 *     con la cantidad real de tours publicados, asi no se vuelve a
 *     desactualizar cuando se publique el proximo.
 *
 * Los tres se resuelven con un script minimo en el head. Cuando el PHP de
 * los snippets pase al plugin, los tres se van a poder hacer en el servidor
 * y este archivo se achica.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Cantidad de tours publicados. Es el numero que el pie deberia mostrar.
 */
function vempra_cuantos_tours() {
	$cuenta = wp_count_posts( VEMPRA_TOUR_CPT );
	return $cuenta ? (int) $cuenta->publish : 0;
}

/**
 * Duracion real del tour, calculada con los horarios que ya estan cargados
 * en la ficha (salida y regreso). El theme solo guarda un contador de dias,
 * asi que todos los tours decian "1 day" aunque duren cinco horas o catorce.
 *
 * Devuelve cadena vacia cuando no se puede afirmar una duracion unica: si
 * falta un horario, si el regreso no es posterior a la salida, o si el campo
 * trae mas de una hora. Ese ultimo caso es real: la Cabalgata al Atardecer
 * sale 16:30 en verano y 14:30 en invierno, y cualquier numero unico seria
 * falso la mitad del ano. En esos casos la ficha vuelve al contador de dias.
 */
function vempra_duracion_tour() {
	if ( ! is_singular( VEMPRA_TOUR_CPT ) ) { return ''; }

	$id = get_queried_object_id();
	if ( ! $id ) { return ''; }

	$hora    = '/\b([01]?[0-9]|2[0-3])[:.]([0-5][0-9])\b/';
	$salida  = (string) get_post_meta( $id, 'tour_departure_time', true );
	$regreso = (string) get_post_meta( $id, 'tour_return_time', true );

	if ( ! preg_match_all( $hora, $salida, $a ) || 1 !== count( $a[0] ) ) { return ''; }
	if ( ! preg_match_all( $hora, $regreso, $b ) || 1 !== count( $b[0] ) ) { return ''; }

	$inicio = (int) $a[1][0] * 60 + (int) $a[2][0];
	$fin    = (int) $b[1][0] * 60 + (int) $b[2][0];
	if ( $fin <= $inicio ) { return ''; }

	$minutos = $fin - $inicio;
	$horas   = intdiv( $minutos, 60 );
	$resto   = $minutos % 60;
	if ( $horas < 1 || $horas > 23 ) { return ''; }

	return $resto ? $horas . ' hs ' . $resto : $horas . ' hs';
}

add_action( 'wp_head', function () {

	$datos = wp_json_encode( array(
		'tours'  => vempra_cuantos_tours(),
		'sitio'  => get_bloginfo( 'name' ),
		'durac'  => vempra_duracion_tour(),
		// Cadenas que el theme escribe a mano y por eso no pasan por
		// gettext: llegan desde inc/textos.php, que es el unico lugar
		// donde vive la traduccion.
		'textos' => function_exists( 'vempra_textos_a_mano' ) ? vempra_textos_a_mano() : array(),
	) );

	// Sin dependencias y sin esperar a nada: la etiqueta del viewport tiene
	// que quedar corregida antes del primer dibujado. El segundo pase en
	// DOMContentLoaded cubre a los themes que la imprimen despues de wp_head.
	?>
<script id="vempra-head">
(function () {
	var D = <?php echo $datos; // phpcs:ignore ?>;

	function zoom() {
		var m = document.querySelector('meta[name="viewport"]');
		if (!m) { return; }
		var c = m.getAttribute('content') || '';
		if (!/maximum-scale|user-scalable/i.test(c)) { return; }
		c = c.replace(/[,;]?\s*maximum-scale\s*=\s*[^,;]*/ig, '')
		     .replace(/[,;]?\s*user-scalable\s*=\s*[^,;]*/ig, '')
		     .replace(/^[\s,;]+/, '').trim();
		m.setAttribute('content', c);
	}

	function logo() {
		var imgs = document.querySelectorAll('.logo_wrapper img');
		for (var i = 0; i < imgs.length; i++) {
			if (!imgs[i].getAttribute('alt')) { imgs[i].setAttribute('alt', D.sitio); }
		}
	}

	// Se reviso el sitio entero: la portada y /tours/ dicen el numero real
	// pero la ficha del tour dice uno de mas, porque ahi el enlace no lleva
	// la clase del pie y se quedaba afuera. Se miran todos los enlaces: son
	// pocos por pagina y la condicion la cumple solo el que interesa.
	function tours() {
		if (!D.tours) { return; }
		var a = document.getElementsByTagName('a');
		for (var i = 0; i < a.length; i++) {
			var t = a[i].textContent;
			if (!/Ver los \d+ tours/.test(t)) { continue; }
			a[i].textContent = t.replace(/Ver los \d+ tours/, 'Ver los ' + D.tours + ' tours');
		}
	}

	// Textos que estan escritos a mano en el theme o en un snippet y por eso
	// no los alcanza ni la traduccion ni el formato de moneda de WooCommerce.
	// Se recorren los nodos de texto porque no tienen una clase propia por
	// la que agarrarlos; se saltean los campos y el codigo para no tocar lo
	// que el visitante escribe ni romper ningun script.
	var CAMBIOS = [
		// El bloque de confianza del checkout prometia algo que no es cierto:
		// la politica real es 100% de reintegro hasta 72 hs antes. Decir "sin
		// cargo" a secas es una promesa que despues hay que desdecir por
		// telefono, y eso cuesta mas que la venta.
		['Reprogramación y cancelación sin cargo', 'Cancelación sin cargo hasta 72 hs antes'],
		['Reprogramacion y cancelacion sin cargo', 'Cancelación sin cargo hasta 72 hs antes'],
		['Recently Viewed Tours', 'Vistos recientemente'],
		['Recently Viewed', 'Vistos recientemente'],
		// El carrito lateral (Xoo Side Cart) guarda sus textos en sus propios
		// ajustes, no en un archivo de traduccion, asi que la tilde hay que
		// ponerla aca. Escrito sin tildes se lee como un error del sitio.
		['Tu carrito esta vacio', 'Tu carrito está vacío']
	];

	// Las que manda el PHP (pagina 404 y "Read More" del theme).
	if (D.textos && D.textos.length) { CAMBIOS = CAMBIOS.concat(D.textos); }

	// El theme imprime la duracion como un contador de dias y en ingles
	// ("1 day"), asi que todos los tours decian lo mismo aunque duren
	// cinco horas o catorce. Se reemplaza por la duracion real que calcula
	// vempra_duracion_tour() con los horarios de la ficha; cuando esa no
	// se puede afirmar, queda el contador traducido ("1 dia").
	//
	// No se traduce con un filtro global porque "day" es una palabra que
	// aparece en medio panel; aca se toca solo el recuadro de atributos.
	function dias() {
		var c = document.querySelectorAll('.tour_attribute_content');
		for (var i = 0; i < c.length; i++) {
			var t = c[i].textContent.replace(/\s+/g, ' ').trim();
			var m = /^(\d+)\s*days?$/i.exec(t);
			if (!m) { continue; }
			c[i].textContent = D.durac ? D.durac
				: m[1] + ' ' + (m[1] === '1' ? 'día' : 'días');
		}
	}

	function textos() {
		if (!document.body) { return; }
		var salteo = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, INPUT: 1, NOSCRIPT: 1 };
		var w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
		var n;
		while ((n = w.nextNode())) {
			if (n.parentNode && salteo[n.parentNode.nodeName]) { continue; }
			var t = n.nodeValue;
			if (!t || t.indexOf('$') === -1 && !/[A-Za-zÁÉÍÓÚáéíóú]/.test(t)) { continue; }
			var antes = t;

			for (var i = 0; i < CAMBIOS.length; i++) {
				if (t.indexOf(CAMBIOS[i][0]) !== -1) { t = t.split(CAMBIOS[i][0]).join(CAMBIOS[i][1]); }
			}

			// Precios con coma de miles: el bloque de vistos recientemente los
			// imprime a la inglesa ($53,000) mientras el resto del sitio usa
			// punto ($53.000). Dos formatos distintos para el mismo numero en
			// la misma pantalla hacen dudar del precio. Solo se cambia la coma
			// seguida de tres digitos exactos: los centavos llevan dos.
			if (t.indexOf('$') !== -1) {
				t = t.replace(/\$\s?\d{1,3}(?:,\d{3})+(?!\d)/g, function (m) {
					return m.replace(/,(?=\d{3})/g, '.');
				});
			}

			if (t !== antes) { n.nodeValue = t; }
		}
	}

	function todo() { zoom(); logo(); tours(); dias(); textos(); }

	todo();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', todo);
	}

	// El bloque de vistos recientemente y el resumen del checkout se vuelven
	// a dibujar por AJAX; si no se repasan, el texto viejo vuelve solo.
	document.addEventListener('DOMContentLoaded', function () {
		if (!window.jQuery) { return; }
		window.jQuery(document.body).on(
			'wc_fragments_refreshed wc_fragments_loaded updated_checkout updated_cart_totals',
			textos
		);
	});
})();
</script>
	<?php
}, 1 );

/**
 * Datos del tour para la medicion.
 *
 * La pagina del tour es un post del tipo `tour`, no un producto, asi que
 * GTM4WP no publica nada de ella: no hay view_item ni ViewContent en toda
 * la ficha. Aca se publica el item una sola vez y assets/medicion.js se
 * encarga de mandarlo.
 *
 * El id que viaja es el del producto de WooCommerce, no el del post: es el
 * mismo que despues manda add_to_cart, y si no coinciden Google y Meta ven
 * dos productos distintos donde hay uno solo.
 */
add_action( 'wp_head', function () {

	if ( ! is_singular( VEMPRA_TOUR_CPT ) ) { return; }

	$tour_id  = (int) get_queried_object_id();
	$producto = vempra_producto_de_tour( $tour_id );
	$precio   = vempra_precio_de_tour( $tour_id );

	$item = array(
		'item_id'   => (string) ( $producto ? $producto->get_id() : $tour_id ),
		'item_name' => wp_strip_all_tags( get_the_title( $tour_id ) ),
		'currency'  => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'ARS',
	);

	if ( $precio > 0 ) { $item['price'] = round( (float) $precio, 2 ); }

	// La categoria sale del producto de WooCommerce, no del post del tour.
	// GTM4WP usa esa misma para el add_to_cart: si aca mandaramos la
	// taxonomia del tour, el mismo producto viajaria como "Wine Tours" al
	// verlo y como "Panoramicos" al agregarlo, y los informes lo cuentan
	// como dos cosas distintas. Si el producto no tiene categoria se cae a
	// la del tour, y si tampoco hay, el item viaja sin categoria.
	$categoria = '';

	if ( $producto ) {
		$terminos = get_the_terms( $producto->get_id(), 'product_cat' );
		if ( ! is_wp_error( $terminos ) && ! empty( $terminos ) ) {
			$categoria = $terminos[0]->name;
		}
	}

	if ( '' === $categoria ) {
		foreach ( get_object_taxonomies( VEMPRA_TOUR_CPT ) as $taxonomia ) {
			$terminos = get_the_terms( $tour_id, $taxonomia );
			if ( is_wp_error( $terminos ) || empty( $terminos ) ) { continue; }
			$categoria = $terminos[0]->name;
			break;
		}
	}

	if ( '' !== $categoria ) { $item['item_category'] = $categoria; }

	echo '<script id="vempra-item">window.VEMPRA_ITEM = ' . wp_json_encode( $item ) . ";</script>\n";

}, 2 );
