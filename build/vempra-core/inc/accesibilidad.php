<?php
/**
 * ACCESIBILIDAD Y FORMATO
 *
 * Lo que quedo del repaso de la tienda en vivo y que se puede resolver desde
 * el plugin. Son arreglos chicos pero de los que se notan: dos de ellos los
 * lee Google y uno lo lee el visitante justo antes de decidir la compra.
 *
 *   1. Dos H1 en la portada. El theme imprime el titulo de la pagina en
 *      #page_caption como H1 ("Vempra Turismo · Tours en Mendoza", que es el
 *      que lleva las palabras clave) y el contenido de la pagina abre con
 *      otro H1, el titulo grande del hero. Con dos H1 ninguno de los dos
 *      manda. Se degrada el del hero a H2: se comprobo en la pagina en vivo
 *      que todas las reglas de estilo del hero apuntan a la clase
 *      (.vempra-v2-hero-title, .vempra-v2-hero-title em) y ninguna a la
 *      etiqueta, asi que cambiar h1 por h2 no mueve un pixel.
 *
 *   2. Siete enlaces sin nombre accesible: el logo, la lupa del menu movil y
 *      los tres botones de cerrar. Todos llevan adentro un icono por CSS, sin
 *      texto, asi que un lector de pantalla los anuncia como "enlace" a
 *      secas. Se les pone aria-label desde el navegador porque los imprime el
 *      theme, fuera de todo filtro.
 *
 *   3. El titulo "Share" del panel de compartir, que el theme escribe a mano
 *      y por eso no lo alcanza la traduccion de inc/textos.php. Se cambia
 *      solo ese h2 y por coincidencia exacta: "Share" es una palabra corta
 *      que aparece en atributos y en clases, y un reemplazo por texto suelto
 *      la tocaria donde no corresponde.
 *
 *   4. El separador de miles de WooCommerce. Lo correcto es el punto; si el
 *      ajuste de la tienda quedara en coma, el precio se leeria a la inglesa
 *      ($55,000) al lado de otros escritos con punto, y dos formatos para el
 *      mismo numero hacen dudar del precio. Con el filtro el formato deja de
 *      depender de un ajuste que se puede tocar sin querer.
 *
 * OJO: el precio grande de la ficha (.single_tour_price) NO pasa por
 * WooCommerce; el theme lo imprime con number_format() y separadores
 * ingleses, fuera de cualquier filtro. Ese sigue corregido por el script de
 * inc/frontend.php, que reescribe la coma de miles despues de dibujar.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * El titulo del hero pasa de H1 a H2 para que en la portada quede un solo H1.
 * Se toca unicamente el H1 que lleva la clase del hero.
 */
add_filter( 'the_content', function ( $html ) {

	if ( ! is_string( $html ) || false === strpos( $html, 'vempra-v2-hero-title' ) ) {
		return $html;
	}

	// Con preg_replace() el texto del titulo se escribiria como reemplazo y
	// PHP leeria cualquier "$" seguido de numeros como una retrorreferencia
	// (el mismo error que se comio el precio de las tarjetas en 1.6.0). Por
	// eso va un callback, que devuelve el texto tal cual.
	$html = preg_replace_callback(
		'#<h1(\s[^>]*\bclass="[^"]*\bvempra-v2-hero-title\b[^"]*"[^>]*)>(.*?)</h1>#is',
		function ( $m ) { return '<h2' . $m[1] . '>' . $m[2] . '</h2>'; },
		$html
	);

	return $html;
}, 21 );

/**
 * Separadores de precio de WooCommerce: punto para los miles, coma para los
 * decimales, que es como se escribe un precio en Argentina.
 */
add_filter( 'wc_get_price_thousand_separator', function () { return '.'; }, 20 );
add_filter( 'wc_get_price_decimal_separator', function () { return ','; }, 20 );

/**
 * Nombres accesibles y el titulo del panel de compartir. Van en el pie para
 * no demorar el dibujado: no cambian nada de lo que se ve, solo lo que
 * anuncia el lector de pantalla.
 */
add_action( 'wp_footer', function () {

	$nombres = array(
		'close_mobile_menu'       => 'Cerrar el menú',
		'mobile_menu_close'       => 'Cerrar el menú',
		'custom_logo'             => 'Ir al inicio',
		'custom_logo_transparent' => 'Ir al inicio',
		'mobile_nav_icon'         => 'Abrir el menú',
		'toTop'                   => 'Volver arriba',
		'close_share'             => 'Cerrar',
	);

	?>
<script id="vempra-a11y">
(function () {
	var NOMBRES = <?php echo wp_json_encode( $nombres ); // phpcs:ignore ?>;

	function nombrar() {
		for (var id in NOMBRES) {
			if (!Object.prototype.hasOwnProperty.call(NOMBRES, id)) { continue; }
			var n = document.getElementById(id);
			// Si el enlace ya tiene texto o aria-label propio, no se toca.
			if (!n || n.getAttribute('aria-label')) { continue; }
			if ((n.textContent || '').trim()) { continue; }
			n.setAttribute('aria-label', NOMBRES[id]);
		}
	}

	function compartir() {
		var h = document.querySelectorAll('.fullscreen_share_content h2');
		for (var i = 0; i < h.length; i++) {
			if ((h[i].textContent || '').trim() === 'Share') { h[i].textContent = 'Compartir'; }
		}
	}

	function todo() { nombrar(); compartir(); }

	todo();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', todo);
	}
})();
</script>
	<?php
}, 99 );
