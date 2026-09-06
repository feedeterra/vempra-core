<?php
/**
 * MEDICION: los eventos del pixel de Meta.
 *
 * El pixel lo pone el plugin oficial "Meta pixel for WordPress" (carpeta
 * official-facebook-pixel). Anda bien para casi todo, pero tiene dos
 * agujeros que se arreglan desde aca.
 *
 * 1) EL PURCHASE SE CONTABA VARIAS VECES
 *
 *    El plugin engancha su Purchase en dos ganchos a la vez y sin dejar
 *    ninguna marca de "este pedido ya lo mande":
 *
 *        woocommerce_thankyou         -> cada vez que se abre "pedido recibido"
 *        woocommerce_payment_complete -> cuando el pago se confirma
 *
 *    Con Mercado Pago eso son varias compras por pedido: la vuelta desde MP,
 *    el F5 del cliente, el link al pedido que llega por mail. Y ademas cuenta
 *    como compra los pedidos que quedan pendientes o se cancelan y nunca se
 *    cobraron, porque la pagina de "pedido recibido" se muestra igual.
 *
 *    Medido entre el 6 de agosto y el 6 de septiembre de 2026: Meta registro
 *    14 compras cuando en WooCommerce hubo 4 pedidos pagos. Las campanas se
 *    optimizan con esas compras infladas y el ROAS del administrador de
 *    anuncios sale al triple del real.
 *
 *    Aca el Purchase sale UNA sola vez por pedido y solo cuando el pedido
 *    esta pagado (processing o completed).
 *
 * 2) NO SE MANDABAN DATOS DEL CLIENTE
 *
 *    El plugin filtra el email, el telefono y el nombre segun la
 *    "coincidencia automatica avanzada" que le baja de Meta; si no la tiene
 *    resuelta, los borra y manda solo IP, navegador y cookie. Por eso la
 *    calidad de coincidencia de todos los eventos estaba en 6.1.
 *
 *    Aca se vuelven a poner en los eventos que van por la API de
 *    conversiones. El SDK de Meta los hashea con SHA-256 antes de mandarlos:
 *    nunca viaja un dato en claro. Se apaga con el filtro
 *    vempra_coincidencia_avanzada devolviendo false.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * La marca que se guarda en el pedido cuando su Purchase ya salio.
 */
function vempra_meta_marca_purchase() {
	return '_vempra_meta_purchase';
}

/**
 * Estados en los que un pedido cuenta como vendido.
 *
 * Un pedido "pending" es uno que entro a Mercado Pago y todavia no pago;
 * "cancelled" y "failed" nunca se cobraron. Ninguno de esos es una compra.
 */
function vempra_estados_de_venta() {
	return apply_filters( 'vempra_estados_de_venta', array( 'processing', 'completed' ) );
}

// ---------------------------------------------------------------------------
// El freno: se corre antes que el plugin (prioridad 1 contra la 40 de el).
// ---------------------------------------------------------------------------
add_action( 'woocommerce_thankyou', 'vempra_meta_control_purchase', 1 );
add_action( 'woocommerce_payment_complete', 'vempra_meta_control_purchase', 1 );

function vempra_meta_control_purchase( $pedido_id ) {
	$pedido = $pedido_id && function_exists( 'wc_get_order' ) ? wc_get_order( $pedido_id ) : false;

	if ( ! $pedido ) {
		vempra_meta_soltar_purchase();
		return;
	}

	$pagado  = in_array( $pedido->get_status(), vempra_estados_de_venta(), true );
	$ya_fue  = (string) $pedido->get_meta( vempra_meta_marca_purchase() );

	if ( ! $pagado || '' !== $ya_fue ) {
		vempra_meta_soltar_purchase();
		return;
	}

	// Queda anotado en el pedido para que el proximo intento no lo repita, y
	// para poder ver desde el panel cuando se mando.
	$pedido->update_meta_data( vempra_meta_marca_purchase(), current_time( 'mysql' ) );
	$pedido->save_meta_data();

	// El pedido queda a mano para ponerle los datos del cliente al evento.
	$GLOBALS['vempra_meta_pedido'] = $pedido;
}

/**
 * Desengancha el Purchase del plugin de Meta en lo que queda de la carga.
 *
 * No se nombra la clase con su namespace completo a proposito: si el plugin
 * la renombra en una version nueva esto la sigue encontrando igual.
 */
function vempra_meta_soltar_purchase() {
	$ganchos = array( 'woocommerce_thankyou', 'woocommerce_payment_complete' );

	foreach ( $ganchos as $gancho ) {
		if ( empty( $GLOBALS['wp_filter'][ $gancho ] ) ) { continue; }

		$registro = $GLOBALS['wp_filter'][ $gancho ];
		if ( ! isset( $registro->callbacks ) || ! is_array( $registro->callbacks ) ) { continue; }

		$sacar = array();
		foreach ( $registro->callbacks as $prioridad => $entradas ) {
			foreach ( $entradas as $entrada ) {
				if ( isset( $entrada['function'] ) && vempra_es_purchase_de_meta( $entrada['function'] ) ) {
					$sacar[] = array( $entrada['function'], $prioridad );
				}
			}
		}

		foreach ( $sacar as $uno ) {
			remove_action( $gancho, $uno[0], $uno[1] );
		}
	}
}

/**
 * Reconoce el Purchase del plugin de Meta y no toca nada mas.
 */
function vempra_es_purchase_de_meta( $llamada ) {
	if ( ! is_array( $llamada ) || 2 !== count( $llamada ) ) { return false; }

	$clase  = is_object( $llamada[0] ) ? get_class( $llamada[0] ) : (string) $llamada[0];
	$metodo = is_string( $llamada[1] ) ? $llamada[1] : '';

	return ( false !== stripos( $clase, 'FacebookWordpressWooCommerce' )
		&& false !== stripos( $metodo, 'purchase' ) );
}

// ---------------------------------------------------------------------------
// Coincidencia avanzada: los datos del cliente en los eventos del servidor.
// ---------------------------------------------------------------------------
add_filter( 'before_conversions_api_event_sent', 'vempra_meta_sumar_datos_cliente', 20 );

function vempra_meta_sumar_datos_cliente( $eventos ) {
	if ( ! is_array( $eventos ) || ! apply_filters( 'vempra_coincidencia_avanzada', true ) ) {
		return $eventos;
	}

	$datos = vempra_meta_datos_del_cliente();
	if ( empty( $datos ) ) { return $eventos; }

	foreach ( $eventos as $evento ) {
		if ( ! is_object( $evento ) || ! method_exists( $evento, 'getUserData' ) ) { continue; }

		$persona = $evento->getUserData();
		if ( ! is_object( $persona ) ) { continue; }

		// Solo se completa lo que venga vacio: si el plugin ya lo puso, manda el.
		$campos = array(
			'email'     => array( 'getEmails',       'setEmail' ),
			'telefono'  => array( 'getPhones',       'setPhone' ),
			'nombre'    => array( 'getFirstNames',   'setFirstName' ),
			'apellido'  => array( 'getLastNames',    'setLastName' ),
			'ciudad'    => array( 'getCities',       'setCity' ),
			'provincia' => array( 'getStates',       'setState' ),
			'cp'        => array( 'getZipCodes',     'setZipCode' ),
			'pais'      => array( 'getCountryCodes', 'setCountryCode' ),
		);

		foreach ( $campos as $clave => $metodos ) {
			if ( empty( $datos[ $clave ] ) ) { continue; }
			if ( ! method_exists( $persona, $metodos[0] ) || ! method_exists( $persona, $metodos[1] ) ) { continue; }

			$puesto = $persona->{$metodos[0]}();
			if ( ! empty( $puesto ) ) { continue; }

			$persona->{$metodos[1]}( $datos[ $clave ] );
		}
	}

	return $eventos;
}

/**
 * De donde salen los datos, en orden de confianza.
 *
 * Primero el pedido que se esta cobrando (es el unico dato seguro cuando el
 * aviso de pago llega por atras, sin navegador). Despues el checkout que el
 * cliente esta completando. Al final, el mail de la cuenta si esta logueado.
 */
function vempra_meta_datos_del_cliente() {
	$datos = array();

	$pedido = isset( $GLOBALS['vempra_meta_pedido'] ) ? $GLOBALS['vempra_meta_pedido'] : null;

	if ( $pedido && is_object( $pedido ) && method_exists( $pedido, 'get_billing_email' ) ) {
		$datos = array(
			'email'     => $pedido->get_billing_email(),
			'telefono'  => $pedido->get_billing_phone(),
			'nombre'    => $pedido->get_billing_first_name(),
			'apellido'  => $pedido->get_billing_last_name(),
			'ciudad'    => $pedido->get_billing_city(),
			'provincia' => $pedido->get_billing_state(),
			'cp'        => $pedido->get_billing_postcode(),
			'pais'      => $pedido->get_billing_country(),
		);
	} elseif ( function_exists( 'WC' ) && WC() && isset( WC()->customer ) && is_object( WC()->customer ) ) {
		$cliente = WC()->customer;
		$datos   = array(
			'email'     => $cliente->get_billing_email(),
			'telefono'  => $cliente->get_billing_phone(),
			'nombre'    => $cliente->get_billing_first_name(),
			'apellido'  => $cliente->get_billing_last_name(),
			'ciudad'    => $cliente->get_billing_city(),
			'provincia' => $cliente->get_billing_state(),
			'cp'        => $cliente->get_billing_postcode(),
			'pais'      => $cliente->get_billing_country(),
		);
	}

	if ( empty( $datos['email'] ) && is_user_logged_in() ) {
		$usuario = wp_get_current_user();
		if ( $usuario && ! empty( $usuario->user_email ) ) {
			$datos['email'] = $usuario->user_email;
		}
	}

	return array_filter( array_map( 'strval', $datos ), 'strlen' );
}
