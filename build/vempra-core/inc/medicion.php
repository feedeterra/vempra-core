<?php
/**
 * MEDICION: los eventos del pixel de Meta.
 *
 * El pixel lo pone el plugin oficial "Meta pixel for WordPress" (carpeta
 * official-facebook-pixel). Anda bien para casi todo, pero tiene cuatro
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
 *    Con Mercado Pago eso son varias compras por un solo pedido: el aviso de
 *    pago que manda MP por atras, la vuelta del cliente al sitio, el F5, el
 *    link al pedido que llega por mail. Cada una de esas vueltas manda un
 *    Purchase nuevo, con identificador nuevo, asi que Meta las cuenta todas.
 *
 *    (La copia del navegador y la del servidor de una MISMA vuelta si las une
 *    Meta solo, porque el plugin les pone el mismo event_id. Lo que se repite
 *    son las vueltas, no las copias.)
 *
 *    Medido entre el 6 de agosto y el 6 de septiembre de 2026: Meta registro
 *    14 compras cuando en WooCommerce hubo 4 pedidos pagos. Las campanas se
 *    optimizan con esas compras infladas y el ROAS del administrador de
 *    anuncios sale mas alto que el de verdad.
 *
 *    Aca el Purchase sale UNA sola vez por pedido y solo cuando el pedido
 *    esta pagado (processing o completed). Los pedidos pendientes, fallados o
 *    cancelados no mandan nada.
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
 *
 * 3) NO HABIA IDENTIFICADOR PROPIO
 *
 *    Los dos arreglos de arriba solo alcanzan a los eventos que tienen un
 *    pedido o un checkout atras. El que entra, mira dos tours y se va sigue
 *    siendo IP, navegador y cookie: por eso PageView y ViewContent quedaban
 *    en 6.1 y AddToCart en 4.4.
 *
 *    El external_id arregla eso. Es un numero propio, del sitio, que viaja
 *    con todos los eventos y le permite a Meta darse cuenta de que las ocho
 *    visitas de la semana son la misma persona. El plugin lo manda solo por
 *    el camino "openbridge", que en esta cuenta no esta activado.
 *
 *    Aca sale siempre. No dice quien es nadie: es un azar de 32 caracteres
 *    guardado en una cookie del propio sitio.
 *
 * 4) EL VIEWCONTENT NO LLEVABA NINGUN DATO DEL TOUR
 *
 *    El plugin lo engancha en la plantilla del producto de WooCommerce, que en
 *    esta tienda no se dibuja nunca porque la URL del producto va con un 301 a
 *    la ficha del tour. Asi que del plugin no salia ninguno.
 *
 *    A Meta igual le llegaban ViewContent, pero de otro lado: de reglas hechas
 *    a mano en el administrador de eventos, que miran la URL o el texto de un
 *    boton. Esas reglas no mandan content_ids, ni value, ni currency. Un
 *    ViewContent sin producto adentro no sirve para remarketing por tour ni
 *    para el catalogo.
 *
 * 5) EL PIXEL SE PRENDIA TAMBIEN EN LAS COPIAS DEL SITIO
 *
 *    El sitio de pruebas es una copia entera de este, con el mismo plugin y el
 *    mismo numero de pixel. Todo lo que se probaba ahi le entraba al pixel de
 *    produccion mezclado con las visitas reales; entre el 2 y el 5 de
 *    septiembre de 2026 la copia mando mas InitiateCheckout que la tienda.
 *
 *    Ahora el pixel corre solo en el dominio de venta.
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
	$ids   = vempra_meta_identificadores();

	// Los datos del cliente solo existen si hay pedido o checkout; el
	// identificador propio existe siempre. Alcanza con tener uno de los dos.
	if ( empty( $datos ) && empty( $ids ) ) { return $eventos; }

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

		// El identificador propio va en todos los eventos, tengan o no datos
		// del cliente. Es lo unico que levanta la calidad de un PageView.
		if ( ! empty( $ids )
			&& method_exists( $persona, 'getExternalIds' )
			&& method_exists( $persona, 'setExternalIds' )
			&& empty( $persona->getExternalIds() ) ) {

			$persona->setExternalIds( $ids );
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

// ---------------------------------------------------------------------------
// Identificador propio (external_id): el mismo numero en todos los eventos.
// ---------------------------------------------------------------------------

/**
 * El nombre de la cookie donde vive el identificador del visitante.
 */
function vempra_nombre_cookie_id() {
	return 'vempra_id';
}

/**
 * Los identificadores que se le mandan a Meta con cada evento.
 *
 * Van dos como mucho: el azar de la cookie (todos los visitantes) y el numero
 * de usuario si esta logueado (para que Meta una la visita de la compu con la
 * del telefono). El de usuario va hasheado porque Meta NO hashea este campo:
 * lo manda tal cual viene. El de la cookie ya es azar, no dice nada de nadie.
 */
function vempra_meta_identificadores() {
	$ids = array();

	$cookie = isset( $_COOKIE[ vempra_nombre_cookie_id() ] ) ? (string) $_COOKIE[ vempra_nombre_cookie_id() ] : '';
	$cookie = preg_replace( '/[^a-f0-9]/', '', strtolower( $cookie ) );

	if ( 32 === strlen( $cookie ) ) {
		$ids[] = $cookie;
	}

	if ( is_user_logged_in() ) {
		$ids[] = hash( 'sha256', 'vempra-usuario-' . get_current_user_id() );
	}

	return apply_filters( 'vempra_identificadores', array_values( array_unique( $ids ) ) );
}

/**
 * Siembra la cookie desde el navegador, no desde PHP.
 *
 * Va en JavaScript a proposito: el cache de LiteSpeed guarda la respuesta
 * entera, cabeceras incluidas. Si la cookie se pusiera con setcookie() de PHP
 * quedaria pegada en la pagina cacheada y todos los visitantes terminarian con
 * el MISMO identificador, que es justo lo contrario de lo que hace falta. El
 * navegador la calcula por visitante y el cache no la toca.
 *
 * Dura un ano, no cruza dominios (SameSite=Lax) y solo viaja por https.
 */
add_action( 'wp_head', 'vempra_sembrar_cookie_id', 1 );

function vempra_sembrar_cookie_id() {
	if ( ! apply_filters( 'vempra_coincidencia_avanzada', true ) ) { return; }

	$nombre = wp_json_encode( vempra_nombre_cookie_id() );
	?>
<script id="vempra-id">
(function () {
	var NOMBRE = <?php echo $nombre; // phpcs:ignore ?>;

	try {
		var puesta = document.cookie.match( new RegExp( '(?:^|; )' + NOMBRE + '=([a-f0-9]{32})' ) );
		if ( puesta ) { return; }

		var azar = '';
		if ( window.crypto && window.crypto.getRandomValues ) {
			var bytes = new Uint8Array( 16 );
			window.crypto.getRandomValues( bytes );
			for ( var i = 0; i < bytes.length; i++ ) {
				azar += ( '0' + bytes[ i ].toString( 16 ) ).slice( -2 );
			}
		} else {
			while ( azar.length < 32 ) {
				azar += Math.floor( Math.random() * 16 ).toString( 16 );
			}
			azar = azar.slice( 0, 32 );
		}

		var seguro = 'https:' === location.protocol ? '; Secure' : '';
		document.cookie = NOMBRE + '=' + azar + '; Max-Age=31536000; Path=/; SameSite=Lax' + seguro;
	} catch ( e ) {}
})();
</script>
	<?php
}

// ---------------------------------------------------------------------------
// 4) EL VIEWCONTENT NO LLEVABA NINGUN DATO DEL TOUR
//
//    El plugin de Meta engancha su ViewContent en woocommerce_after_single_product,
//    que es un gancho de la PLANTILLA del producto de WooCommerce. En esta tienda
//    esa plantilla no se dibuja nunca: inc/tours.php manda la URL del producto a
//    la ficha del tour con un 301, asi que el visitante siempre esta parado en un
//    post del tipo "tour" y el gancho no corre. De los cinco eventos del plugin,
//    cuatro salian (AddToCart, InitiateCheckout y Purchase van por ganchos del
//    servidor de WooCommerce, que no dependen de la plantilla) y el ViewContent
//    no salia en ninguna de las dieciocho fichas.
//
//    A Meta igual le llegaban ViewContent, y muchos, pero no del plugin: los
//    dispara una regla hecha a mano en el administrador de eventos que mira si
//    la URL contiene /tour/. Esa regla no manda ni content_ids, ni value, ni
//    currency: es un "alguien miro algo" pelado. Con eso no se puede armar
//    publico de remarketing por tour ni conectar el catalogo, porque Meta no
//    sabe cual de los dieciocho tours se miro.
//
//    Aca el evento se arma con las funciones del propio plugin de Meta, no a
//    mano. Eso trae de arriba el mismo formato de content_ids que ya usan el
//    AddToCart y el Purchase (SKU_id, para que Meta los una), el event_id que
//    despareja la copia del navegador de la del servidor, y los datos del
//    cliente. Si Meta renombra sus clases, esto se apaga solo y no rompe nada.
// ---------------------------------------------------------------------------
add_action( 'wp_footer', 'vempra_meta_viewcontent_de_tour', 5 );

function vempra_meta_viewcontent_de_tour() {

	static $ya_salio = false;

	if ( $ya_salio || is_admin() || ! is_singular( VEMPRA_TOUR_CPT ) ) { return; }
	if ( ! vempra_es_sitio_de_venta() ) { return; }

	$fabrica = '\FacebookPixelPlugin\Core\ServerEventFactory';
	$cola    = '\FacebookPixelPlugin\Core\FacebookServerSideEvent';
	$woo     = '\FacebookPixelPlugin\Integration\FacebookWordpressWooCommerce';
	$utiles  = '\FacebookPixelPlugin\Core\FacebookPluginUtils';

	// Si el plugin de Meta no esta, o cambio de nombre, no pasa nada.
	if ( ! class_exists( $fabrica ) || ! class_exists( $cola ) || ! class_exists( $woo ) ) { return; }
	if ( ! method_exists( $fabrica, 'safe_create_event' ) ) { return; }
	if ( ! method_exists( $woo, 'createViewContentEvent' ) || ! method_exists( $woo, 'enqueuePixelCode' ) ) { return; }
	if ( ! method_exists( $cola, 'get_instance' ) ) { return; }

	// Las visitas del equipo no se miden, igual que en el resto del plugin.
	if ( class_exists( $utiles ) && method_exists( $utiles, 'is_internal_user' ) && $utiles::is_internal_user() ) { return; }

	$producto = function_exists( 'vempra_producto_de_tour' ) ? vempra_producto_de_tour( get_queried_object_id() ) : null;
	if ( ! $producto ) { return; }

	$evento = $fabrica::safe_create_event(
		'ViewContent',
		'vempra_meta_datos_viewcontent',
		array( $producto ),
		'woocommerce'
	);

	if ( ! is_object( $evento ) ) { return; }

	$ya_salio = true;

	// false = va a la cola. El plugin la vacia en wp_footer con prioridad 10,
	// que es despues de esta; por eso el evento sale en el mismo lote que los
	// demas y con una sola llamada a la API de conversiones.
	$cola::get_instance()->track( $evento, false );

	// Y la copia del navegador, con el mismo event_id.
	$woo::enqueuePixelCode( $evento );
}

/**
 * Los datos del ViewContent de una ficha de tour.
 *
 * Se pide el armado al plugin de Meta y despues se corrige una sola cosa: el
 * "value". Meta lo saca de get_price(), que en un producto de Bookings puede
 * venir en cero cuando el precio esta cargado en los tipos de pasajero en vez
 * del campo _price. Hoy los dieciocho tours tienen _price cargado, asi que esto
 * no se usa; queda de red por si manana se carga un tour de la otra forma, para
 * que Meta no reciba un tour que vale cero.
 */
function vempra_meta_datos_viewcontent( $producto ) {

	$woo   = '\FacebookPixelPlugin\Integration\FacebookWordpressWooCommerce';
	$datos = (array) $woo::createViewContentEvent( $producto );

	if ( empty( $datos['value'] ) && function_exists( 'vempra_precio_de_producto' ) ) {
		$precio = (float) vempra_precio_de_producto( $producto );
		if ( $precio > 0 ) { $datos['value'] = $precio; }
	}

	return $datos;
}

// ---------------------------------------------------------------------------
// 5) EL CANDADO DE DOMINIO
//
//    El sitio de pruebas de Hostinger es una copia entera de este: mismo
//    plugin de Meta, misma configuracion, mismo numero de pixel. Cada vez que
//    se prueba algo ahi, esos eventos entran al pixel de produccion mezclados
//    con las visitas reales. Medido en el propio administrador de eventos:
//    entre el 2 y el 5 de septiembre de 2026 la copia mando mas
//    InitiateCheckout que la tienda de verdad.
//
//    Eso ensucia dos cosas que despues cuestan plata: los publicos de
//    remarketing se llenan de gente que nunca entro a la tienda, y la
//    optimizacion de las campanas aprende de un embudo que no existe.
//
//    Aca se apagan las dos patas. La API de conversiones se corta con el
//    filtro que el propio plugin ofrece. El pixel del navegador se apaga
//    sacandole al plugin sus ganchos del frente: sin el fbq en la pagina
//    tampoco corren las reglas hechas a mano en el administrador de eventos,
//    que son las que se colaban desde la copia porque miran el texto de un
//    boton y no el dominio.
//
//    Para cambiar el dominio de venta no hace falta tocar este archivo:
//
//        add_filter( 'vempra_dominio_de_venta', function () {
//            return 'otro.dominio.com';
//        } );
// ---------------------------------------------------------------------------
add_action( 'init', 'vempra_meta_candado_de_dominio', 5 );

function vempra_meta_candado_de_dominio() {

	if ( vempra_es_sitio_de_venta() ) { return; }

	// La API de conversiones. El filtro es del plugin: devolviendo una lista
	// vacia no sale nada, ni lo del plugin ni lo nuestro.
	add_filter( 'before_conversions_api_event_sent', '__return_empty_array', 99 );

	// Y el pixel del navegador. En el panel se lo deja tranquilo para poder
	// seguir viendo su configuracion desde la copia.
	if ( ! is_admin() ) {
		vempra_meta_sacar_ganchos_del_plugin();
	}
}

/**
 * El dominio donde la tienda vende de verdad.
 */
function vempra_dominio_de_venta() {
	return apply_filters( 'vempra_dominio_de_venta', 'tienda.vempra.tur.ar' );
}

/**
 * Si este sitio es el de venta o una copia.
 *
 * Se mira home_url() y no la cabecera del pedido: home_url es la identidad del
 * sitio, sirve igual en el cron y en WP-CLI, y una copia siempre la tiene
 * cambiada porque WordPress no arranca de otra forma.
 */
function vempra_es_sitio_de_venta() {

	$casa = wp_parse_url( home_url(), PHP_URL_HOST );
	$casa = is_string( $casa ) ? strtolower( $casa ) : '';
	$casa = preg_replace( '/^www\./', '', $casa );

	return ( vempra_dominio_de_venta() === $casa );
}

/**
 * Le saca al plugin de Meta todos sus ganchos.
 *
 * Se recorre el registro de ganchos de WordPress y se sacan las llamadas que
 * pertenecen al espacio de nombres del plugin. No se nombra ninguna clase en
 * particular a proposito: si Meta agrega o renombra integraciones, esto las
 * agarra igual. Corre en init con prioridad 5, que es despues del init con
 * prioridad 0 donde el plugin registra todo.
 */
function vempra_meta_sacar_ganchos_del_plugin() {

	if ( empty( $GLOBALS['wp_filter'] ) || ! is_array( $GLOBALS['wp_filter'] ) ) { return; }

	$sacar = array();

	foreach ( $GLOBALS['wp_filter'] as $gancho => $registro ) {

		if ( ! isset( $registro->callbacks ) || ! is_array( $registro->callbacks ) ) { continue; }

		foreach ( $registro->callbacks as $prioridad => $entradas ) {
			foreach ( $entradas as $entrada ) {
				if ( isset( $entrada['function'] ) && vempra_es_del_plugin_de_meta( $entrada['function'] ) ) {
					$sacar[] = array( $gancho, $entrada['function'], $prioridad );
				}
			}
		}
	}

	foreach ( $sacar as $uno ) {
		remove_filter( $uno[0], $uno[1], $uno[2] );
	}
}

/**
 * Reconoce una llamada que sale del plugin de Meta.
 */
function vempra_es_del_plugin_de_meta( $llamada ) {

	$clase = '';

	if ( is_array( $llamada ) && isset( $llamada[0] ) ) {
		$clase = is_object( $llamada[0] ) ? get_class( $llamada[0] ) : (string) $llamada[0];
	} elseif ( is_string( $llamada ) ) {
		$clase = $llamada;
	}

	if ( '' === $clase ) { return false; }

	return ( 0 === strpos( ltrim( $clase, '\\' ), 'FacebookPixelPlugin\\' ) );
}

// ---------------------------------------------------------------------------
// 6) LA VERIFICACION DEL DOMINIO EN META
//
//    Meta pide verificar el dominio para poder elegir que enlaces se pueden
//    usar en los anuncios y para que la tienda mande eventos con permiso
//    propio y no prestado. El token sale del administrador comercial, en
//    Configuracion del negocio -> Seguridad de la marca -> Dominios.
//
//    Se guarda en una opcion, no en el codigo, para no tener que publicar una
//    version nueva del plugin cada vez que Meta lo cambie:
//
//        update_option( 'vempra_meta_token_dominio', 'el-token-que-da-meta' );
// ---------------------------------------------------------------------------
add_action( 'wp_head', 'vempra_meta_verificacion_de_dominio', 1 );

function vempra_meta_verificacion_de_dominio() {

	if ( ! vempra_es_sitio_de_venta() ) { return; }

	$token = trim( (string) get_option( 'vempra_meta_token_dominio', '' ) );

	if ( '' === $token ) { return; }

	printf(
		'<meta name="facebook-domain-verification" content="%s" />' . "\n",
		esc_attr( $token )
	);
}
