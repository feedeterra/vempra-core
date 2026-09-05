<?php
/**
 * TIENDA: carrito, checkout y pedido recibido.
 *
 * Todo lo que antes vivia en Code Snippets y toca el proceso de compra:
 *
 *   - Sin "Tambien te puede interesar" en el carrito           (snippet 38)
 *   - Cupon automatico llegando con ?cupon=CODIGO en la URL     (snippet 41)
 *   - Checkout sin campos de direccion                          (snippet 57)
 *   - Cartel "que pasa despues de reservar" + sellos            (snippet 58)
 *   - Campo de cupon siempre visible dentro del resumen         (snippet 60)
 *   - Transferencia como metodo de pago por defecto             (snippet 62)
 *   - Boton "Ir a pagar y reservar" despues de las notas        (snippet 64)
 *   - Cartel de "gracias" en la pagina de pedido recibido       (snippet 65)
 *   - Corte de reservas a las 18:00 del dia anterior            (snippet 66)
 *
 * El CSS de estas pantallas esta en assets/checkout.css y el JavaScript en
 * assets/checkout.js; los dos se cargan solo en el checkout (ver inc/assets.php).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ---------------------------------------------------------------------------
// Carrito: sin productos sugeridos debajo.
// ---------------------------------------------------------------------------
add_action( 'init', function () {
	remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
	// El cupon plegable de arriba del checkout se reemplaza por el campo
	// siempre visible dentro del resumen (mas abajo).
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
} );

// ---------------------------------------------------------------------------
// Cupon por URL: tienda.vempra.tur.ar/?cupon=NIEVE2026 lo guarda 30 dias en
// una cookie y lo aplica solo cuando hay algo en el carrito.
// ---------------------------------------------------------------------------
add_action( 'init', function () {
	if ( empty( $_GET['cupon'] ) ) { return; }
	$code = sanitize_text_field( wp_unslash( $_GET['cupon'] ) );
	if ( headers_sent() ) { return; }
	setcookie( 'vempra_pending_coupon', $code, time() + 30 * DAY_IN_SECONDS, '/', '', is_ssl(), true );
	$_COOKIE['vempra_pending_coupon'] = $code;
} );

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
	if ( empty( $_COOKIE['vempra_pending_coupon'] ) || $cart->is_empty() ) { return; }
	$code = sanitize_text_field( wp_unslash( $_COOKIE['vempra_pending_coupon'] ) );
	if ( ! $cart->has_discount( $code ) ) {
		$cart->apply_coupon( $code );
	}
} );

// ---------------------------------------------------------------------------
// Checkout: solo nombre, apellido, email y telefono.
// ---------------------------------------------------------------------------
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	foreach ( array( 'company', 'address_1', 'address_2', 'city', 'postcode', 'state', 'country' ) as $campo ) {
		unset( $fields['billing'][ 'billing_' . $campo ] );
	}
	return $fields;
} );

add_filter( 'woocommerce_default_checkout_payment_method', function () {
	return 'bacs';
} );

/**
 * Los sellos que aparecen arriba del boton de pago. Se pueden cambiar desde
 * afuera con el filtro vempra_sellos_checkout.
 */
function vempra_sellos_checkout() {
	return (array) apply_filters( 'vempra_sellos_checkout', array(
		'🔒 Pago 100% seguro',
		'★ 5.0 en Google',
		'✓ Operador oficial RNAV Nº 18414',
		'✓ Reprogramación y cancelación sin cargo',
		'✓ Pagás en cuotas',
	) );
}

add_action( 'woocommerce_after_checkout_billing_form', function () {
	?>
	<div class="vempra-aviso-asesor">
		<h3>¿Qué pasa después de reservar?</h3>
		<p>Una vez confirmada tu reserva, <strong>un asesor de Vempra se comunica con vos</strong> para coordinar los detalles de tu salida —el punto de encuentro o dónde te pasamos a buscar, según el tour— y para enviarte tus <strong>vouchers</strong> e información adicional.</p>
	</div>
	<?php
} );

add_action( 'woocommerce_review_order_before_submit', function () {
	echo '<div class="vempra-sellos"><div class="vempra-sellos-lista">';
	foreach ( vempra_sellos_checkout() as $sello ) {
		echo '<span>' . esc_html( $sello ) . '</span>';
	}
	echo '</div></div>';
} );

add_action( 'woocommerce_review_order_before_payment', function () {
	if ( ! wc_coupons_enabled() ) { return; }
	?>
	<div class="vempra-cupon">
		<label for="vempra_coupon_code">¿Tenés un cupón de descuento?</label>
		<div class="vempra-cupon-fila">
			<input type="text" id="vempra_coupon_code" class="input-text" placeholder="Ej: NIEVE2026" />
			<button type="button" class="button" id="vempra_apply_coupon">Aplicar</button>
		</div>
	</div>
	<?php
} );

add_action( 'woocommerce_after_order_notes', function () {
	echo '<a href="#payment" class="vempra-ir-pago" id="vempra-ir-pago">Ir a pagar y reservar →</a>';
} );

// ---------------------------------------------------------------------------
// Pedido recibido.
// ---------------------------------------------------------------------------
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	if ( ! $order_id ) { return; }
	?>
	<div class="vempra-aviso-asesor vempra-aviso-gracias">
		<h3>¡Gracias por tu reserva! ¿Qué sigue ahora?</h3>
		<p><strong>Un asesor de Vempra se va a comunicar con vos</strong> para coordinar los detalles de tu salida —el punto de encuentro o dónde te pasamos a buscar, según el tour— y para enviarte tus <strong>vouchers</strong> e información adicional. Si querés, también podés escribirnos directo por WhatsApp.</p>
	</div>
	<?php
}, 5 );

// ---------------------------------------------------------------------------
// Zona horaria del sitio: Mendoza (UTC-3, sin horario de verano).
//
// WordPress venia en UTC+0 y el corte de reservas de abajo se mide con la
// hora del sitio: a las 15:00 de Mendoza ya eran las 18:00 en UTC y se
// cerraba la venta del dia siguiente tres horas antes de lo previsto. Las
// fechas de los pedidos tambien salian corridas. Se fija por codigo para
// que no dependa de un ajuste en Ajustes > Generales que cualquiera puede
// tocar sin querer; Royal MCP no permite escribir esta opcion.
// ---------------------------------------------------------------------------
add_filter( 'pre_option_timezone_string', function () { return 'America/Argentina/Mendoza'; } );
add_filter( 'pre_option_gmt_offset', function () { return -3; } );

// ---------------------------------------------------------------------------
// Corte de reservas: hasta las 18:00 del dia anterior a la salida.
//
// Se expresa en DIAS, nunca en minutos u horas: el calendario de WooCommerce
// Bookings pasa el valor al datepicker de jQuery UI como "+Nm" / "+Nh", y ese
// datepicker lee la "m" como MESES (la 1.9.0 mandaba "+780m" y el calendario
// saltaba a septiembre de 2027 sin ningun dia reservable). Con dias:
//   - antes de las 18:00 (hora de Mendoza) se puede reservar desde manana;
//   - a partir de las 18:00, desde pasado manana.
// La hora de corte se cambia con el filtro vempra_hora_corte_reservas.
// ---------------------------------------------------------------------------
function vempra_dias_minimos_reserva() {
	$corte = (int) apply_filters( 'vempra_hora_corte_reservas', 18 );
	return ( (int) current_time( 'G' ) >= $corte ) ? 2 : 1;
}

add_filter( 'woocommerce_bookings_min_date_value', function ( $value, $product_id ) {
	return vempra_dias_minimos_reserva();
}, 20, 2 );

add_filter( 'woocommerce_bookings_min_date_unit', function ( $unit, $product_id ) {
	return 'day';
}, 20, 2 );
