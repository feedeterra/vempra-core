<?php
/**
 * TOURS: la ficha y su relacion con el producto de WooCommerce.
 *
 *   - Shortcode [vempra_booking_form id=PRODUCTO] que imprime el formulario
 *     de reserva del producto adentro de la ficha del tour         (snippet 9)
 *   - Tabla producto -> tour, redireccion 301 de la pagina del
 *     producto a la del tour y foto del tour copiada al producto  (snippet 36)
 *   - "Coste de la reserva" se lee "Valor"                        (snippet 37)
 *   - En celular, el formulario de reserva se mueve arriba del
 *     texto "Lo que vas a vivir"                                   (snippet 52)
 *   - LiteSpeed no difiere ni combina el JavaScript de los tours
 *     ni cachea la ficha: la disponibilidad tiene que ser fresca  (snippets 53 y 56)
 *   - Royal MCP puede escribir la moneda y las resenas del theme  (snippet 55)
 *
 * El JavaScript de la ficha que no es de la ficha optimizada (badges,
 * FAQ de compra, respaldo de "Valor") esta en assets/tour-extras.js.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Producto de WooCommerce => pagina de tour. El meta tour_booking_product ya
 * une tour con producto; esta tabla es el camino inverso, para redirigir la
 * URL del producto a la ficha y para copiar la foto.
 */
function vempra_product_to_tour_map() {
	return (array) apply_filters( 'vempra_product_to_tour_map', array(
		110 => 483, // Alta Montana
		122 => 529, // Canon del Atuel
		123 => 530, // Villavicencio medio dia
		124 => 528, // Bodegas Caminos del Vino
		125 => 531, // Villavicencio 4x4
		126 => 535, // Termas Cacheuta
		127 => 532, // Cabalgata con almuerzo
		128 => 533, // Cabalgata criolla
		129 => 536, // Combo termas
		130 => 537, // Full day aventura
		131 => 538, // Rafting medio dia
		132 => 526, // Wine tour Maipu
		133 => 527, // Wine tour Lujan
		134 => 754, // Wine tour picnic Lujan
		237 => 534, // Cabalgata sunset
		369 => 523, // Las Lenas
		370 => 524, // Los Puquios
		371 => 525, // Penitentes
	) );
}

// La pagina del producto no se visita: manda a la ficha del tour.
add_action( 'wp', function () {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) { return; }
	$map = vempra_product_to_tour_map();
	$id  = get_queried_object_id();
	if ( empty( $map[ $id ] ) ) { return; }
	$url = get_permalink( $map[ $id ] );
	if ( $url ) {
		wp_redirect( $url, 301 );
		exit;
	}
}, 1 );

// Al cambiar la foto destacada de un tour, el producto recibe la misma.
add_action( 'updated_post_meta', function ( $meta_id, $post_id, $meta_key ) {
	if ( '_thumbnail_id' !== $meta_key || VEMPRA_TOUR_CPT !== get_post_type( $post_id ) ) { return; }
	$reverse = array_flip( vempra_product_to_tour_map() );
	if ( isset( $reverse[ $post_id ] ) ) {
		$thumb = get_post_thumbnail_id( $post_id );
		if ( $thumb ) { set_post_thumbnail( $reverse[ $post_id ], $thumb ); }
	}
}, 10, 3 );

// ---------------------------------------------------------------------------
// [vempra_booking_form id=124]
// ---------------------------------------------------------------------------
add_shortcode( 'vempra_booking_form', function ( $atts ) {
	$atts       = shortcode_atts( array( 'id' => 0 ), $atts );
	$product_id = (int) $atts['id'];
	if ( ! $product_id ) { return ''; }

	global $product, $post;
	$original_product = $product;
	$original_post    = $post;

	$product = wc_get_product( $product_id );
	if ( ! $product ) { return ''; }
	$post = get_post( $product_id );
	setup_postdata( $post );

	ob_start();
	echo '<div class="vempra-booking-form">';
	woocommerce_template_single_add_to_cart();
	echo '</div>';
	$output = ob_get_clean();

	$product = $original_product;
	$post    = $original_post;
	wp_reset_postdata();

	return $output;
} );

// ---------------------------------------------------------------------------
// "Coste de la reserva" -> "Valor".
// ---------------------------------------------------------------------------
function vempra_booking_cost_label( $translation, $text, $domain ) {
	if ( 'woocommerce-bookings' !== $domain ) { return $translation; }
	$map = array(
		'Booking cost'        => 'Valor',
		'Booking cost: %s'    => 'Valor: %s',
		'Coste de la reserva' => 'Valor',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}
add_filter( 'gettext', 'vempra_booking_cost_label', 20, 3 );
add_filter( 'gettext_with_context', function ( $translation, $text, $context, $domain ) {
	return vempra_booking_cost_label( $translation, $text, $domain );
}, 20, 4 );

// ---------------------------------------------------------------------------
// Celular: el formulario de reserva sube antes de "Lo que vas a vivir".
//
// Va inline y con data-no-optimize porque tiene que correr antes de que el
// visitante vea la ficha; si LiteSpeed lo difiriera, el formulario saltaria
// de lugar a la vista.
// ---------------------------------------------------------------------------
add_action( 'wp_footer', function () {
	if ( ! is_singular( VEMPRA_TOUR_CPT ) ) { return; }
	?>
	<style id="vempra-booking-mobile">
	@media (max-width:768px){
	  #page_content_wrapper .inner_wrapper > .sidebar_wrapper{ display:none !important; }
	  #page_content_wrapper .sidebar_content{ clear:none !important; float:none !important; width:100% !important; margin-top:12px !important; }
	}
	</style>
	<script data-no-optimize="1" data-no-defer="1" data-cfasync="false">
	(function(){
	  var ph=null, moved=false;
	  function run(){
	    var booking=document.querySelector('.single_tour_booking_wrapper');
	    if(!booking) return;
	    var content=document.querySelector('.single_tour_content');
	    var isMobile=window.matchMedia('(max-width:768px)').matches;
	    if(isMobile && !moved && content){
	      ph=document.createComment('vempra-booking-ph');
	      booking.parentNode.insertBefore(ph, booking);
	      var t=null,hs=content.querySelectorAll('h2,h3,h4');
	      for(var i=0;i<hs.length;i++){ if(/lo que vas a vivir/i.test(hs[i].textContent||'')){t=hs[i];break;} }
	      booking.style.cssText += ';float:none;width:100%;margin:18px 0 26px;';
	      if(t) content.insertBefore(booking,t); else content.appendChild(booking);
	      moved=true;
	    } else if(!isMobile && moved && ph && ph.parentNode){
	      booking.style.float=''; booking.style.width=''; booking.style.margin='';
	      ph.parentNode.insertBefore(booking, ph);
	      ph.parentNode.removeChild(ph); ph=null;
	      moved=false;
	    }
	  }
	  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',run); else run();
	  window.addEventListener('load',run);
	  window.addEventListener('resize',run);
	  setTimeout(run,1500);
	})();
	</script>
	<?php
}, 99 );

// ---------------------------------------------------------------------------
// LiteSpeed en tours y productos.
// ---------------------------------------------------------------------------
add_action( 'litespeed_init', function () {
	if ( ! is_singular( VEMPRA_TOUR_CPT ) ) { return; }
	foreach ( array( 'optm-js_defer', 'optm-js_inline_defer', 'optm-js_comb', 'optm-js_comb_ext_inl', 'optm-js_min', 'guest', 'guest_optm' ) as $conf ) {
		do_action( 'litespeed_conf_force', $conf, false );
	}
} );

add_action( 'wp', function () {
	if ( is_singular( VEMPRA_TOUR_CPT ) || ( function_exists( 'is_product' ) && is_product() ) ) {
		do_action( 'litespeed_control_set_nocache', 'Vempra: la reserva necesita datos frescos' );
	}
} );

// Royal MCP: dos ajustes del theme que se editan desde afuera.
add_filter( 'royal_mcp_writable_theme_mods', function ( $mods ) {
	$mods[] = 'tg_tour_currency';
	$mods[] = 'tg_tour_single_review';
	return $mods;
} );
