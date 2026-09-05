<?php
/**
 * PANEL: Vempra -> Precios
 *
 * Una sola pantalla que muestra, tour por tour, que precio tenia guardado la
 * pagina, que precio tiene el producto de WooCommerce y cual se esta
 * mostrando. Sirve para verificar de un vistazo que no quedo ningun tour sin
 * precio ni ningun numero viejo dando vueltas.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Lee tour_price salteando nuestro propio filtro, para poder mostrar el valor
 * que quedo escrito en la base de datos.
 */
function vempra_precio_guardado( $tour_id ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare(
		"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
		$tour_id,
		VEMPRA_META_PRECIO
	) );
}

add_action( 'admin_menu', function () {
	add_menu_page(
		'Vempra',
		'Vempra',
		'manage_woocommerce',
		'vempra-precios',
		'vempra_pantalla_precios',
		'dashicons-tickets-alt',
		56
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'vempra_core', 'vempra_precio_unico', array(
		'type'              => 'string',
		'default'           => 'si',
		'sanitize_callback' => function ( $v ) { return 'si' === $v ? 'si' : 'no'; },
	) );
} );

function vempra_pantalla_precios() {

	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

	$tours = get_posts( array(
		'post_type'      => VEMPRA_TOUR_CPT,
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	$activo = vempra_precio_unico_activo();
	$sin    = 0;

	echo '<div class="wrap"><h1>Precios de los tours</h1>';

	echo '<form method="post" action="options.php" style="margin:16px 0 22px">';
	settings_fields( 'vempra_core' );
	// El hidden va ANTES del checkbox: si el checkbox esta destildado no se
	// envia, y el que llega es el "no". Al reves, el "no" pisaria al "si".
	echo '<input type="hidden" name="vempra_precio_unico" value="no">';
	echo '<label><input type="checkbox" name="vempra_precio_unico" value="si" ' . checked( $activo, true, false ) . '> ';
	echo '<strong>Precio unico</strong>: el precio se lee siempre del producto de WooCommerce.</label>';
	echo '<p class="description">Destildalo si alguna vez necesitas volver al comportamiento anterior. No se pierde ningun dato.</p>';
	echo '<br><input type="hidden" name="vempra_nieve" value="no">';
	echo '<label><input type="checkbox" name="vempra_nieve" value="si" ' . checked( vempra_nieve_activa(), true, false ) . '> ';
	echo 'Mostrar los banners de temporada de nieve (la barra de arriba con el cupón NIEVE2026 y el banner de la portada).</label> ';
	submit_button( 'Guardar', 'secondary', 'submit', false );
	echo '</form>';

	echo '<table class="widefat striped"><thead><tr>';
	echo '<th>Tour</th><th>Producto</th><th>Guardado en la pagina</th><th>Precio del producto</th><th>Se muestra</th><th>Estado</th><th>Tipos de pasajero</th>';
	echo '</tr></thead><tbody>';

	foreach ( $tours as $tour ) {

		$producto = vempra_producto_de_tour( $tour->ID );
		$guardado = vempra_precio_guardado( $tour->ID );
		$deWoo    = $producto ? (float) $producto->get_price() : 0.0;
		$final    = vempra_precio_de_tour( $tour->ID );

		if ( ! $producto ) {
			$estado = '<span style="color:#b32d2e">sin producto vinculado</span>';
			$sin++;
		} elseif ( $final <= 0 ) {
			$estado = '<span style="color:#b32d2e">sin precio</span>';
			$sin++;
		} elseif ( $deWoo <= 0 ) {
			$estado = '<span style="color:#1d4ed8">tarifa Adulto</span>';
		} elseif ( (float) $guardado !== $deWoo ) {
			$estado = '<span style="color:#996800">corregido</span>';
		} else {
			$estado = '<span style="color:#2c7a3f">al dia</span>';
		}

		$tipos = array();
		foreach ( vempra_tipos_de_pasajero( $producto ) as $nombre => $costo ) {
			$tipos[] = esc_html( $nombre ) . ': ' . esc_html( number_format( $costo, 0, ',', '.' ) );
		}

		printf(
			'<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td><strong>%s</strong></td><td>%s</td><td style="font-size:12px;color:#555">%s</td></tr>',
			esc_url( get_edit_post_link( $tour->ID ) ),
			esc_html( $tour->post_title ),
			$producto
				? sprintf( '<a href="%s">#%d</a>', esc_url( get_edit_post_link( $producto->get_id() ) ), $producto->get_id() )
				: '&mdash;',
			$guardado ? esc_html( number_format( (float) $guardado, 0, ',', '.' ) ) : '&mdash;',
			$deWoo > 0 ? esc_html( number_format( $deWoo, 0, ',', '.' ) ) : '&mdash;',
			$final > 0 ? esc_html( '$' . number_format( $final, 0, ',', '.' ) ) : '&mdash;',
			$estado,
			$tipos ? implode( ' &middot; ', $tipos ) : '&mdash;'
		);
	}

	echo '</tbody></table>';

	echo '<p style="margin-top:14px">';
	printf( '%d tours. ', count( $tours ) );
	if ( $sin ) {
		printf( '<strong style="color:#b32d2e">%d sin precio para mostrar.</strong> ', $sin );
		echo 'Hay que cargarles el precio en el producto de WooCommerce.';
	} else {
		echo 'Todos con precio.';
	}
	echo '</p>';

	echo '<h2 style="margin-top:26px">Como se lee esta tabla</h2><ul style="list-style:disc;padding-left:20px;max-width:760px">';
	echo '<li><strong>Al dia</strong>: la pagina y el producto coinciden.</li>';
	echo '<li><strong>Corregido</strong>: la pagina tenia guardado un precio distinto y se esta mostrando el del producto. No hay nada que hacer.</li>';
	echo '<li><strong>Tarifa Adulto</strong>: el producto no tiene precio de vitrina y el precio real vive en los tipos de pasajero, asi que se muestra el costo del Adulto. Es el mismo numero que la ficha ya mostraba en la cabecera.</li>';
	echo '<li><strong>Sin precio</strong>: no hay de donde sacarlo. Hay que cargarlo en el producto.</li>';
	echo '</ul></div>';
}
