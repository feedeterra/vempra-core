<?php
/**
 * TEXTOS
 *
 * El sitio esta en espanol pero WooCommerce Bookings y el theme dejan
 * cadenas en ingles a la vista del visitante: los globitos del calendario,
 * los avisos de validacion del formulario de reserva y la pagina 404
 * entera.
 *
 * Se resuelve con un filtro de traduccion en vez de Loco Translate porque:
 *
 *   - Loco escribe los .mo dentro de wp-content/languages, que no esta
 *     versionado: cualquier actualizacion del plugin o una restauracion del
 *     backup se los lleva puestos y el ingles vuelve solo.
 *   - Aca las cadenas viajan con el plugin, asi que produccion recibe la
 *     traduccion en el mismo paquete que el resto de los cambios.
 *
 * El filtro corre ANTES de que Bookings arme sus objetos localizados de
 * JavaScript, asi que las mismas traducciones alcanzan tanto al PHP como a
 * los i18n_* que terminan en el HTML.
 *
 * El mapa se busca por la cadena original en ingles y no por dominio de
 * texto: son frases lo bastante particulares como para no chocar con nada,
 * y asi el mismo mapa sirve aunque el theme o el plugin cambien de dominio.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Traducciones. Clave: la cadena original, tal cual la escribe el plugin o
 * el theme (con el %d incluido donde lo haya).
 */
function vempra_traducciones() {

	static $mapa = null;

	if ( null !== $mapa ) { return $mapa; }

	$mapa = array(

		// Calendario de la reserva: son los globitos que ve el visitante al
		// pasar el mouse por cada dia.
		'This date is available'                              => 'Fecha disponible',
		'This date is unavailable'                            => 'Fecha no disponible',
		'This date is fully booked and unavailable'           => 'Fecha completa, sin lugares',
		'This date is partially booked - but bookings still remain'
			=> 'Quedan pocos lugares para esta fecha',
		'Choose a Start Date'                                 => 'Elegí la fecha de inicio',
		'Choose an End Date'                                  => 'Elegí la fecha de fin',
		'To clear selection, pick a new start date'           => 'Para borrar la selección, elegí otra fecha de inicio',
		'Choose a date above to see available times.'         => 'Elegí una fecha para ver los horarios disponibles.',

		// Validacion del formulario. Estas dos son las que frenan la compra:
		// si aparecen en ingles el visitante no entiende que le falta.
		'Date is required - please choose one above'          => 'Falta la fecha: elegí un día en el calendario',
		'The minimum persons per group is %d'                 => 'El mínimo de personas por grupo es de %d',
		'The maximum persons per group is %d'                 => 'El máximo de personas por grupo es de %d',
		'Please fill out all required fields.'                => 'Completá todos los campos obligatorios.',
		'Please select the options for your booking and make sure duration rules apply.'
			=> 'Elegí las opciones de tu reserva para poder continuar.',

		// Esperas y errores del calculo de precio.
		'Please wait ...'                                     => 'Un momento...',
		'Please wait, the latest available slots are being processed in the background.'
			=> 'Un momento, estamos buscando los lugares disponibles.',
		"We weren't able to get that information. Please contact the store owner for help."
			=> 'No pudimos calcular el precio. Escribinos por WhatsApp y lo resolvemos.',
		'Store server time: '                                 => 'Hora del sitio: ',
		'View cart'                                           => 'Ver el carrito',

		// Pagina 404 del theme.
		'404 Not Found!'                                      => 'No encontramos esta página',
		"We're sorry, the page you have looked for does not exist in our content! Perhaps you would like to go to our homepage or try searching below."
			=> 'La página que buscás no existe o cambió de dirección. Podés volver al inicio o buscar el tour que te interesa acá abajo.',
		'Or try to browse our latest posts instead?'          => '¿O preferís mirar las últimas notas del blog?',
		// El theme no imprime la frase entera: la parte el punto de
		// exclamacion y saca cada mitad por su lado (una en el subtitulo
		// y otra sobre el buscador), asi que cada mitad va por separado.
		"We're sorry, the page you have looked for does not exist in our content!"
			=> 'La página que buscás no existe o cambió de dirección.',
		'Perhaps you would like to go to our homepage or try searching below.'
			=> 'Podés volver al inicio o buscar el tour que te interesa acá abajo.',
		'Read More'                                           => 'Leer más',

		// Ficha del tour: lo que el theme imprime en ingles alrededor del
		// precio y del boton de reserva. Son las palabras que el visitante
		// lee justo antes de decidir, asi que no pueden quedar en ingles.
		'Per Person'                                          => 'por persona',
		'Book now'                                            => 'Reservar ahora',
		'Share this tour'                                     => 'Compartir este tour',
		'Share'                                               => 'Compartir',
		'Your browser must support JavaScript in order to make a booking.'
			=> 'Necesitás tener JavaScript activado para poder reservar.',
		'Age'                                                 => 'Edad',
		'Availability'                                        => 'Cupos',
		'Departure Time'                                      => 'Hora de salida',
		'Return Time'                                         => 'Hora de regreso',

		// Formulario de opiniones al pie de la ficha.
		'Write A Review'                                      => 'Escribí tu opinión',
		'Accomodation'                                        => 'Alojamiento',
		'Destination'                                         => 'Destino',
		'Meals'                                               => 'Comidas',
		'Transport'                                           => 'Transporte',
		'Value For Money'                                     => 'Relación precio-calidad',
		'Overall'                                             => 'Valoración general',
	);

	return $mapa;
}

/**
 * Aplica el mapa a una cadena. Devuelve la traduccion o la cadena tal cual
 * si no esta en el mapa.
 */
function vempra_traducir( $texto ) {
	$mapa = vempra_traducciones();
	return isset( $mapa[ $texto ] ) ? $mapa[ $texto ] : $texto;
}

/**
 * Se engancha en las cuatro variantes de gettext para cubrir __(), _x(),
 * _n() y _nx(). En las plurales se traduce la forma que gettext eligio, que
 * es la que llega en $traducido cuando no hay .mo cargado.
 */
add_filter( 'gettext', function ( $traducido, $original ) {
	return $traducido === $original ? vempra_traducir( $original ) : $traducido;
}, 20, 2 );

add_filter( 'gettext_with_context', function ( $traducido, $original ) {
	return $traducido === $original ? vempra_traducir( $original ) : $traducido;
}, 20, 2 );

add_filter( 'ngettext', function ( $traducido, $singular, $plural, $numero ) {
	$original = ( 1 === (int) $numero ) ? $singular : $plural;
	return $traducido === $original ? vempra_traducir( $original ) : $traducido;
}, 20, 4 );

add_filter( 'ngettext_with_context', function ( $traducido, $singular, $plural, $numero ) {
	$original = ( 1 === (int) $numero ) ? $singular : $plural;
	return $traducido === $original ? vempra_traducir( $original ) : $traducido;
}, 20, 4 );

/**
 * Las cadenas que el theme o un snippet escriben a mano no pasan por
 * gettext, asi que no hay filtro que las alcance. Se pasan al JavaScript
 * del head, que las reemplaza en el texto ya dibujado.
 *
 * Se mandan solo las que se vieron escritas a mano en el sitio: cuantas
 * menos, menos trabajo hace el recorrido de nodos en cada pagina.
 */
function vempra_textos_a_mano() {
	$mapa  = vempra_traducciones();
	$claves = array(
		'404 Not Found!',
		"We're sorry, the page you have looked for does not exist in our content! Perhaps you would like to go to our homepage or try searching below.",
		'Or try to browse our latest posts instead?',
		"We're sorry, the page you have looked for does not exist in our content!",
		'Perhaps you would like to go to our homepage or try searching below.',
		'Read More',
	);

	$salida = array();
	foreach ( $claves as $clave ) {
		if ( isset( $mapa[ $clave ] ) ) { $salida[] = array( $clave, $mapa[ $clave ] ); }
	}

	return $salida;
}
