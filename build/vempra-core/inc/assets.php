<?php
/**
 * ASSETS
 *
 * De donde salio cada archivo:
 *
 *   assets/vempra.css  <-  el post 22 de WordPress, donde 165 KB de CSS
 *                          vivian como texto de una entrada.
 *   assets/vempra.js   <-  el contenido de la pagina del Tour Bodegas, donde
 *                          estaba embebido como <script> en medio del texto.
 *
 * Eso obligaba a escribir el JavaScript mutilado (sin &&, sin comentarios,
 * sin renglones en blanco) porque el editor lo reescribia, y ademas dejaba
 * todo fuera de cualquier control de versiones.
 *
 * El CSS aplica a todo el sitio, asi que se carga en todas las paginas.
 * El JavaScript espera el HTML de la ficha optimizada, asi que se carga solo
 * en los tours que ya lo tienen.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Tours que reciben la ficha optimizada.
 *
 * Ya no hace falta anotar aca cada tour que se optimiza: vempra_ficha_optimizada()
 * reconoce la ficha por su propio HTML. Esta lista queda como excepcion, para
 * forzar un tour cuyo contenido todavia no tenga las marcas, y se puede
 * ampliar desde afuera sin tocar el plugin:
 *
 *   add_filter( 'vempra_tours_optimizados', function ( $ids ) {
 *       $ids[] = 754;
 *       return $ids;
 *   } );
 */
function vempra_tours_optimizados() {
	return (array) apply_filters( 'vempra_tours_optimizados', array( 528 ) );
}

/**
 * Marcas que solo existen en el HTML de la ficha optimizada. Alcanza con que
 * aparezca una para saber que ese tour ya tiene el HTML que el JavaScript
 * espera.
 */
function vempra_marcas_ficha_optimizada() {
	return array( 'vempra-quick', 'vempra-precio-ficha' );
}

/**
 * Si esta ficha lleva el JavaScript de la ficha optimizada.
 *
 * Se decide mirando el contenido del tour, no una lista de IDs: asi, cada vez
 * que se replique la ficha a un tour nuevo, el JavaScript se carga solo y no
 * hay que volver a instalar el plugin. La lista de IDs sigue existiendo como
 * excepcion, para forzar un tour cuyo HTML todavia no tenga las marcas.
 */
function vempra_ficha_optimizada() {
	if ( ! is_singular( VEMPRA_TOUR_CPT ) ) { return false; }

	$id = (int) get_queried_object_id();
	if ( in_array( $id, array_map( 'intval', vempra_tours_optimizados() ), true ) ) {
		return true;
	}

	$post = get_post( $id );
	if ( ! $post || '' === (string) $post->post_content ) { return false; }

	foreach ( vempra_marcas_ficha_optimizada() as $marca ) {
		if ( false !== strpos( $post->post_content, $marca ) ) { return true; }
	}

	return false;
}

/**
 * URL de un archivo de assets, con la version metida en el NOMBRE.
 *
 * En este sitio no sirve el "?ver=" de siempre: algo del lado del hosting le
 * saca la cadena de consulta a las URLs de los assets (se comprobo en
 * staging: 87 archivos en la ficha del tour y ninguno con "ver="), y encima
 * el CDN de Hostinger los guarda siete dias. Resultado: cada actualizacion
 * del plugin tardaba una semana en verse.
 *
 * Como la cadena de consulta no sobrevive, la version viaja en la ruta:
 *   assets/ux.1757003872.js
 * y el .htaccess de assets/ la borra antes de buscar el archivo, asi que en
 * el disco sigue existiendo un solo ux.js. El numero es la fecha de
 * modificacion del archivo, de modo que cambia solo en cada despliegue y
 * navegador y CDN se ven obligados a pedirlo de nuevo.
 *
 * Devuelve cadena vacia si el archivo no existe.
 */
function vempra_asset_url( $archivo ) {
	$ruta = VEMPRA_CORE_DIR . 'assets/' . $archivo;
	if ( ! file_exists( $ruta ) ) { return ''; }

	$punto = strrpos( $archivo, '.' );
	if ( false === $punto ) { return VEMPRA_CORE_URL . 'assets/' . $archivo; }

	return VEMPRA_CORE_URL . 'assets/'
		. substr( $archivo, 0, $punto )
		. '.' . filemtime( $ruta )
		. substr( $archivo, $punto );
}

add_action( 'wp_enqueue_scripts', function () {

	// El mapa de precios se publica en todas las paginas: lo usa tanto el JS
	// del plugin como cualquier copia que todavia quede embebida.
	$mapa = function_exists( 'vempra_mapa_precios' ) ? vempra_mapa_precios() : array();
	wp_register_script( 'vempra-precios', '', array(), VEMPRA_CORE_VERSION, false );
	wp_enqueue_script( 'vempra-precios' );
	wp_add_inline_script(
		'vempra-precios',
		'window.VEMPRA_PRECIOS = ' . wp_json_encode( $mapa ) . ';',
		'before'
	);

	$css = VEMPRA_CORE_DIR . 'assets/vempra.css';
	if ( file_exists( $css ) ) {
		wp_enqueue_style( 'vempra-core', vempra_asset_url( 'vempra.css' ), array(), null );
	}

	// La correccion del value de add_to_cart va en toda pagina donde pueda
	// haber un formulario de reserva: la ficha del tour y la del producto.
	// Es chica y se carga en el pie, no bloquea nada.
	$medicion = VEMPRA_CORE_DIR . 'assets/medicion.js';
	$hay_form = is_singular( VEMPRA_TOUR_CPT ) || ( function_exists( 'is_product' ) && is_product() );
	if ( $hay_form && file_exists( $medicion ) ) {
		wp_enqueue_script( 'vempra-medicion', vempra_asset_url( 'medicion.js' ), array(), null, true );
	}

	// La confirmacion al agregar al carrito, el reintento del calculo de
	// precio y la correccion del globito del carrito.
	//
	// Va en todas las paginas y no solo donde hay formulario de reserva: el
	// numero del carrito en la cabecera lo imprime el theme dentro del HTML,
	// y LiteSpeed sirve ese HTML cacheado. En la portada dice 0 con cuatro
	// tours adentro del carrito. WooCommerce refresca su propio carrito
	// lateral pero no toca ese numero, asi que hay que corregirlo aca.
	$ux = VEMPRA_CORE_DIR . 'assets/ux.js';
	if ( file_exists( $ux ) ) {
		wp_enqueue_script( 'vempra-ux', vempra_asset_url( 'ux.js' ), array(), null, true );
	}

	if ( ! vempra_ficha_optimizada() ) { return; }

	$js = VEMPRA_CORE_DIR . 'assets/vempra.js';
	if ( file_exists( $js ) ) {
		// En el pie: el script espera a DOMContentLoaded igual, pero asi no
		// bloquea el dibujado de la pagina.
		wp_enqueue_script( 'vempra-core', vempra_asset_url( 'vempra.js' ), array( 'vempra-precios' ), null, true );
	}

}, 20 );
