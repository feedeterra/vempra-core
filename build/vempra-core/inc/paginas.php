<?php
/**
 * PAGINAS: ajustes de paginas puntuales.
 *
 *   - Las paginas legales llevan la clase vempra-legal-page en el body,
 *     que es lo que engancha su CSS en assets/sitio.css           (snippet 43)
 *   - Datos estructurados (Article + FAQPage) de la guia del Tour
 *     Alta Montana, post 590                                      (snippet 46)
 *
 * El filtro que abria la pagina Tours con ?filter=nieve (snippet 33) ya
 * estaba incluido en el JavaScript de esa pagina (assets/tienda.js), asi que
 * no se duplica. El acordeon de Preguntas frecuentes y el copiar-email y mapa
 * de Contacto (snippets 31 y 32) estan en assets/sitio.js.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 167 Terminos · 168 Privacidad · 169 Defensa del consumidor ·
 * 170 Arrepentimiento · 172 Cancelaciones
 */
function vempra_paginas_legales() {
	return (array) apply_filters( 'vempra_paginas_legales', array( 167, 168, 169, 170, 172 ) );
}

add_filter( 'body_class', function ( $classes ) {
	if ( is_page( vempra_paginas_legales() ) ) {
		$classes[] = 'vempra-legal-page';
	}
	return $classes;
} );

/**
 * Datos estructurados por entrada del blog. Para sumar otra guia se agrega
 * una entrada al array con el ID del post y sus bloques.
 */
function vempra_schema_posts() {
	return (array) apply_filters( 'vempra_schema_posts', array(
		590 => array(
			array(
				'@context'      => 'https://schema.org',
				'@type'         => 'Article',
				'headline'      => 'Tour Alta Montaña Mendoza: guía completa 2026 (itinerario, precio, qué llevar)',
				'description'   => 'Todo sobre el Tour Alta Montaña de Mendoza en 2026: itinerario hora por hora, qué incluye, mejor época, precios, qué llevar y cómo reservar.',
				'author'        => array( '@type' => 'Organization', 'name' => 'Vempra Turismo Mendoza', 'url' => 'https://tienda.vempra.tur.ar/' ),
				'publisher'     => array(
					'@type' => 'Organization',
					'name'  => 'Vempra Turismo Mendoza',
					'logo'  => array( '@type' => 'ImageObject', 'url' => 'https://tienda.vempra.tur.ar/wp-content/uploads/2026/03/cropped-logo-vempra.png' ),
				),
				'datePublished' => '2026-05-28',
				'dateModified'  => '2026-05-28',
			),
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => array(
					array( '@type' => 'Question', 'name' => '¿Cuánto dura el Tour Alta Montaña?', 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'Aproximadamente 12 horas en total, desde las 7:00 hasta las 19:30.' ) ),
					array( '@type' => 'Question', 'name' => '¿Cuánto cuesta el Tour Alta Montaña en 2026?', 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'Entre $80.000 y $110.000 por persona en tour grupal. Tours premium o en van llegan a $130.000. Precios por debajo de $60.000 son sospechosamente bajos.' ) ),
					array( '@type' => 'Question', 'name' => '¿El tour cruza a Chile?', 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'No. El tour llega hasta el Mirador del Aconcagua y vuelve. El paso fronterizo Cristo Redentor está más adelante, pero no es parte del recorrido.' ) ),
					array( '@type' => 'Question', 'name' => '¿Cuál es la mejor época para hacer el Tour Alta Montaña?', 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'De octubre a abril, cuando los caminos están limpios y las temperaturas son templadas. En invierno (mayo-septiembre) el paisaje nevado es espectacular pero la ruta puede cerrar por temporales.' ) ),
				),
			),
		),
	) );
}

add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) ) { return; }
	$todos = vempra_schema_posts();
	$id    = get_queried_object_id();
	if ( empty( $todos[ $id ] ) ) { return; }
	foreach ( $todos[ $id ] as $bloque ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $bloque, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 20 );
