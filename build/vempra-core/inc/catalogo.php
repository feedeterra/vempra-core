<?php
/**
 * CATALOGO: la pagina "Tours en Mendoza" (331) generada por el plugin.
 *
 * Hasta la 1.9.0 esa pagina era un bloque HTML escrito a mano en el editor:
 * dieciocho tarjetas con el titulo, la descripcion, la foto y el precio de
 * cada tour adentro del HTML. El precio lo corregia inc/tarjetas.php al
 * vuelo, pero todo lo demas —sumar un tour, cambiar una descripcion, mover
 * una tarjeta— era editar HTML a mano.
 *
 * Desde la 1.10.0 la pagina entera sale del shortcode [vempra_tours]:
 *
 *   - La cabecera cuenta los tours publicados de verdad.
 *   - Cada tarjeta se arma con los datos de vempra_catalogo_tours(): el
 *     titulo corto, la categoria, la descripcion y la foto se escriben ahi;
 *     el enlace y el precio salen del tour y de su producto de WooCommerce.
 *   - Un tour publicado que no figure en la lista aparece igual al final,
 *     con su titulo, su extracto y su foto destacada, para que nunca falte
 *     un tour en la tienda.
 *   - Los filtros y el orden siguen en assets/tienda.js, sin cambios.
 *
 * El HTML que quedo guardado en la pagina ya no se usa: mientras tenga el
 * bloque viejo, el plugin lo reemplaza por el shortcode al dibujarla. Lo
 * ideal es dejar la pagina con solo [vempra_tours] en el editor.
 *
 * Para sumar un tour: se agrega una entrada al array. Para cambiar un texto:
 * se edita aca. Para cambiar un precio: en el producto de WooCommerce, como
 * siempre. Todo se puede modificar desde afuera con los filtros
 * vempra_catalogo_tours, vempra_catalogo_categorias y vempra_catalogo_textos.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Los botones de filtro, en orden. La clave es la que llevan las tarjetas en
 * data-category y la que se puede pasar en la URL con ?filter=nieve.
 */
function vempra_catalogo_categorias() {
	return (array) apply_filters( 'vempra_catalogo_categorias', array(
		'panoramicos' => 'Panorámicos',
		'wine-tours'  => 'Wine Tours',
		'cabalgatas'  => 'Cabalgatas',
		'aventura'    => 'Aventura',
		'nieve'       => 'Nieve',
		'relax'       => 'Relax y Termas',
	) );
}

/**
 * Los textos fijos de la pagina.
 */
function vempra_catalogo_textos() {
	return (array) apply_filters( 'vempra_catalogo_textos', array(
		'titulo'      => 'Tours en Mendoza',
		'subtitulo'   => 'Elegí la experiencia perfecta para tu viaje. Filtrá, ordená y reservá online en 2 minutos.',
		'sin_result'  => 'No encontramos tours con ese filtro. Probá con otra categoría.',
		'ayuda_h'     => '¿Necesitás ayuda para elegir?',
		'ayuda_p'     => 'Contanos qué te gusta y te recomendamos el tour ideal para tu viaje.',
		'ayuda_btn'   => 'Hablanos por WhatsApp',
		'ayuda_msg'   => 'Hola! Necesito ayuda para elegir un tour',
		'cta'         => 'Ver tour →',
	) );
}

/**
 * La barra de confianza debajo de la cabecera.
 */
function vempra_catalogo_confianza() {
	return (array) apply_filters( 'vempra_catalogo_confianza', array(
		'<span class="vempra-shop-trustbar-stars">★★★★★</span> <em>5.0 en Google · +150 reseñas</em>',
		'Cancelación gratis <em>hasta 72 hs antes</em>',
		'Pagás en <em>3 cuotas sin interés</em>',
		'Operador oficial <em>Legajo RNAV 18414</em>',
	) );
}

/**
 * Las tarjetas, en el orden "Recomendados". Campos:
 *
 *   id         el tour (post type tour); de ahi salen el enlace y el precio
 *   titulo     el titulo corto de la tarjeta (el del tour es mas largo)
 *   cats       una o mas claves de vempra_catalogo_categorias(), separadas
 *              por espacio
 *   categoria  el texto que se ve arriba del titulo
 *   desc       una o dos lineas
 *   meta       duracion y dias, como se muestran
 *   foto       clase de foto (vempra-tour-bg-<foto>, definidas en
 *              assets/vempra.css); si se deja vacia se usa la foto
 *              destacada del tour
 *   badge      etiqueta sobre la foto (opcional)
 *   feature    true en la tarjeta grande de arriba (opcional)
 *   cuotas     texto debajo del precio (opcional)
 */
function vempra_catalogo_tours() {
	return (array) apply_filters( 'vempra_catalogo_tours', array(
		array(
			'id'        => 483,
			'titulo'    => 'Tour Alta Montaña',
			'cats'      => 'panoramicos',
			'categoria' => 'Panorámico · Cordillera',
			'desc'      => 'Full Day por la cordillera hasta el Mirador del Aconcagua a 3.200 metros. Potrerillos, Uspallata, Puente del Inca y Penitentes.',
			'meta'      => array( '12 hs', 'Todos los días' ),
			'foto'      => 'altamontana',
			'badge'     => 'Más vendido',
			'feature'   => true,
			'cuotas'    => '3 cuotas sin interés',
		),
		array(
			'id'        => 527,
			'titulo'    => 'Wine Tour Luján de Cuyo',
			'cats'      => 'wine-tours',
			'categoria' => 'Wine Tour',
			'desc'      => '3 bodegas de alta gama + almuerzo gourmet de 4 pasos con maridaje. La experiencia top del Malbec.',
			'meta'      => array( '9 hs', 'Lun a Sáb' ),
			'foto'      => 'winetour',
			'badge'     => 'Premium',
		),
		array(
			'id'        => 526,
			'titulo'    => 'Wine Tour Maipú',
			'cats'      => 'wine-tours',
			'categoria' => 'Wine Tour',
			'desc'      => '3 bodegas + olivícola + almuerzo gourmet con maridaje. Las bodegas históricas del Malbec.',
			'meta'      => array( '8.5 hs', 'Lun a Sáb' ),
			'foto'      => 'bodegas',
			'badge'     => 'Premium',
		),
		array(
			'id'        => 528,
			'titulo'    => 'Tour Bodegas Mendoza',
			'cats'      => 'wine-tours',
			'categoria' => 'Wine Tour',
			'desc'      => 'Wine tour medio día por 2 bodegas en Maipú. 6 vinos + tabla. Ideal para tiempo limitado.',
			'meta'      => array( '5 hs', 'Lun a Sáb' ),
			'foto'      => 'bodegas2',
		),
		array(
			'id'        => 529,
			'titulo'    => 'Cañón del Atuel',
			'cats'      => 'panoramicos',
			'categoria' => 'Panorámico',
			'desc'      => 'El gran cañón mendocino en San Rafael. 4 miradores + Lago Valle Grande. Geología única.',
			'meta'      => array( '14 hs', 'Todo el año' ),
			'foto'      => 'atuel',
		),
		array(
			'id'        => 530,
			'titulo'    => 'Tour Villavicencio',
			'cats'      => 'panoramicos',
			'categoria' => 'Panorámico',
			'desc'      => 'Los 365 caracoles + hotel termal histórico + reserva natural con guanacos y cóndores.',
			'meta'      => array( '5.5 hs', 'Todo el año' ),
			'foto'      => 'villavicencio',
		),
		array(
			'id'        => 531,
			'titulo'    => 'Villavicencio 4x4',
			'cats'      => 'aventura',
			'categoria' => 'Aventura',
			'desc'      => 'La versión aventurera de Villavicencio. Zonas remotas accesibles solo en 4x4, mejor avistaje de fauna.',
			'meta'      => array( '9 hs', 'Todo el año' ),
			'foto'      => 'villa4x4',
		),
		array(
			'id'        => 534,
			'titulo'    => 'Cabalgata al Atardecer',
			'cats'      => 'cabalgatas',
			'categoria' => 'Cabalgata',
			'desc'      => '2h a caballo al sunset + cena criolla bajo las estrellas con fogón. Ideal para parejas.',
			'meta'      => array( '6 hs', 'Lun a Sáb' ),
			'foto'      => 'cabalgata',
			'badge'     => 'Premium',
		),
		array(
			'id'        => 532,
			'nombre'    => 'Cabalgata con Almuerzo',
			'titulo'    => 'Cabalgata con Almuerzo Criollo',
			'cats'      => 'cabalgatas',
			'categoria' => 'Cabalgata',
			'desc'      => 'Cabalgata 2h + asado a la cruz con vino + tarde de descanso en estancia tradicional.',
			'meta'      => array( '7 hs', 'Lun a Sáb' ),
			'foto'      => 'cabalgata2',
		),
		array(
			'id'        => 533,
			'titulo'    => 'Cabalgata Criolla',
			'cats'      => 'cabalgatas',
			'categoria' => 'Cabalgata',
			'desc'      => 'Cabalgata 1h30 en finca de Maipú + asado a la parrilla con vino. Combinable con bodegas.',
			'meta'      => array( '6 hs', 'Lun a Sáb' ),
			'foto'      => 'cabalgata3',
		),
		array(
			'id'        => 535,
			'titulo'    => 'Full Day Spa Cacheuta',
			'cats'      => 'relax',
			'categoria' => 'Relax y Termas',
			'desc'      => 'Día completo en Termas de Cacheuta. 8 piletas termales + sauna + almuerzo buffet incluido.',
			'meta'      => array( '10 hs', 'Todo el año' ),
			'foto'      => 'termas',
			'badge'     => 'Premium',
		),
		array(
			'id'        => 536,
			'titulo'    => 'Combo Termas + Rafting',
			'cats'      => 'aventura relax',
			'categoria' => 'Aventura · Relax',
			'desc'      => 'Rafting nivel principiante por la mañana + tarde en Termas Cacheuta. Aventura y relax en un día.',
			'meta'      => array( '10 hs', 'Todo el año' ),
			'foto'      => 'combo',
		),
		array(
			'id'        => 537,
			'titulo'    => 'Full Day Aventura',
			'cats'      => 'aventura',
			'categoria' => 'Aventura',
			'desc'      => 'Rafting + canopy + rappel en el cajón del río Mendoza. 3 actividades en un día.',
			'meta'      => array( '10 hs', 'Oct-Abr' ),
			'foto'      => 'aventura',
		),
		array(
			'id'        => 538,
			'titulo'    => 'Rafting Medio Día',
			'cats'      => 'aventura',
			'categoria' => 'Aventura',
			'desc'      => '12 km de rafting en el río Mendoza con rápidos clase II-III. Apto principiantes.',
			'meta'      => array( '5 hs', 'Oct-Abr' ),
			'foto'      => 'rafting',
		),
		array(
			'id'        => 523,
			'nombre'    => 'Las Leñas',
			'titulo'    => 'Tour Las Leñas',
			'cats'      => 'nieve',
			'categoria' => 'Nieve',
			'desc'      => 'Esquí en Las Leñas con traslado desde Mendoza. El centro de esquí más prestigioso de Argentina.',
			'meta'      => array( 'Full Day', 'Jun-Sep' ),
			'foto'      => 'lenas',
		),
		array(
			'id'        => 524,
			'nombre'    => 'Los Puquios',
			'titulo'    => 'Tour Los Puquios',
			'cats'      => 'nieve',
			'categoria' => 'Nieve',
			'desc'      => 'El centro de nieve familiar más cercano a Mendoza. Ideal para primeros contactos con la nieve.',
			'meta'      => array( 'Full Day', 'Jun-Sep' ),
			'foto'      => 'puquios',
		),
		array(
			'id'        => 525,
			'nombre'    => 'Penitentes',
			'titulo'    => 'Tour Penitentes',
			'cats'      => 'nieve',
			'categoria' => 'Nieve',
			'desc'      => 'Esquí en Penitentes a 200 km de Mendoza. 7 pistas para todos los niveles, escuela completa.',
			'meta'      => array( 'Full Day', 'Jun-Sep' ),
			'foto'      => 'penitentes',
		),
		array(
			'id'        => 754,
			'titulo'    => 'Wine Tour con Picnic',
			'cats'      => 'wine-tours',
			'categoria' => 'Wine Tour',
			'desc'      => 'Viñedos de Finca Bandini en carrito de golf y picnic gourmet con maridaje en los jardines de Bodega Bonfanti, al aire libre.',
			'meta'      => array( '8 hs' ),
			'foto'      => 'bodegas2',
		),
	) );
}

/**
 * Tours publicados que no estan en la lista: van al final con lo que tienen.
 */
function vempra_catalogo_faltantes( $listados ) {
	$ids = get_posts( array(
		'post_type'      => VEMPRA_TOUR_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post__not_in'   => $listados,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	$out = array();
	foreach ( $ids as $id ) {
		$out[] = array(
			'id'        => $id,
			'titulo'    => get_the_title( $id ),
			'cats'      => '',
			'categoria' => 'Tour',
			'desc'      => wp_strip_all_tags( get_the_excerpt( $id ) ),
			'meta'      => array(),
			'foto'      => '',
		);
	}
	return $out;
}

/**
 * Una tarjeta. Devuelve cadena vacia si el tour no existe o no esta publicado.
 */
function vempra_catalogo_tarjeta( $t ) {
	$t = wp_parse_args( $t, array(
		'titulo' => '', 'cats' => '', 'categoria' => '', 'desc' => '', 'meta' => array(),
		'foto' => '', 'badge' => '', 'feature' => false, 'cuotas' => '', 'nombre' => '',
	) );
	$id = (int) $t['id'];
	if ( ! $id || 'publish' !== get_post_status( $id ) ) { return ''; }

	$textos = vempra_catalogo_textos();
	$url    = get_permalink( $id );
	$titulo = '' !== $t['titulo'] ? $t['titulo'] : get_the_title( $id );
	// Nombre para el orden A-Z; si no se indica, es el titulo.
	$nombre = '' !== $t['nombre'] ? $t['nombre'] : $titulo;
	$precio = function_exists( 'vempra_precio_de_tour' ) ? (float) vempra_precio_de_tour( $id ) : 0;

	$clase = 'vempra-shop-card' . ( $t['feature'] ? ' vempra-shop-card-feature' : '' );

	$foto_clase = '';
	$foto_style = '';
	if ( '' !== $t['foto'] ) {
		$foto_clase = ' vempra-tour-bg-' . sanitize_html_class( $t['foto'] );
	} else {
		$img = get_the_post_thumbnail_url( $id, 'large' );
		if ( $img ) { $foto_style = ' style="background-image:url(\'' . esc_url( $img ) . '\')"'; }
	}

	$meta = '';
	if ( ! empty( $t['meta'] ) ) {
		$partes = array_map( function ( $m ) { return '<span>' . esc_html( $m ) . '</span>'; }, (array) $t['meta'] );
		$meta   = '<div class="vempra-shop-card-meta">' . implode( '<span>·</span>', $partes ) . '</div>';
	}

	$h  = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $clase ) . '"';
	$h .= ' data-category="' . esc_attr( $t['cats'] ) . '"';
	$h .= ' data-price="' . (int) round( $precio ) . '"';
	$h .= ' data-name="' . esc_attr( $nombre ) . '">';
	$h .= '<div class="vempra-shop-card-image' . $foto_clase . '"' . $foto_style . '>';
	if ( '' !== $t['badge'] ) {
		$h .= '<span class="vempra-shop-card-badge' . ( $t['feature'] ? ' vempra-shop-card-badge-top' : '' ) . '">' . esc_html( $t['badge'] ) . '</span>';
	}
	$h .= '</div>';
	$h .= '<div class="vempra-shop-card-body">';
	$h .= '<div class="vempra-shop-card-category">' . esc_html( $t['categoria'] ) . '</div>';
	$h .= '<h3 class="vempra-shop-card-title">' . esc_html( $titulo ) . '</h3>';
	$h .= '<p class="vempra-shop-card-desc">' . esc_html( $t['desc'] ) . '</p>';
	$h .= $meta;
	$h .= '<div class="vempra-shop-card-footer"><div class="vempra-shop-card-price">';
	if ( $precio > 0 ) {
		$h .= '<span class="vempra-shop-card-price-label">' . ( $t['feature'] ? 'Desde, por persona' : 'Desde' ) . '</span>';
		$h .= '<span class="vempra-shop-card-price-value">' . esc_html( vempra_precio_en_pesos( $precio ) ) . '</span>';
	}
	if ( '' !== $t['cuotas'] ) {
		$h .= '<span class="vempra-shop-card-price-cuotas">' . esc_html( $t['cuotas'] ) . '</span>';
	}
	$h .= '</div><span class="vempra-shop-card-cta">' . esc_html( $textos['cta'] ) . '</span></div>';
	$h .= '</div></a>';

	return $h;
}

/**
 * [vempra_tours]: la pagina completa.
 */
add_shortcode( 'vempra_tours', function () {
	$textos  = vempra_catalogo_textos();
	$cuantos = function_exists( 'vempra_cuantos_tours' ) ? vempra_cuantos_tours() : 0;
	$lista   = vempra_catalogo_tours();
	$ids     = array_map( function ( $t ) { return (int) $t['id']; }, $lista );
	$lista   = array_merge( $lista, vempra_catalogo_faltantes( $ids ) );
	$wa      = function_exists( 'vempra_whatsapp' ) ? vempra_whatsapp() : '5492616527094';

	ob_start();
	?>
<div class="vempra-shop-wrap" data-vempra="catalogo">

  <header class="vempra-shop-hero">
    <div class="vempra-shop-hero-overlay"></div>
    <div class="vempra-shop-hero-content">
      <?php if ( $cuantos > 0 ) : ?>
      <div class="vempra-shop-hero-eyebrow">+<?php echo (int) $cuantos; ?> experiencias disponibles</div>
      <?php endif; ?>
      <h1 class="vempra-shop-hero-title"><?php echo esc_html( $textos['titulo'] ); ?></h1>
      <p class="vempra-shop-hero-subtitle"><?php echo esc_html( $textos['subtitulo'] ); ?></p>
    </div>
  </header>

  <section class="vempra-shop-trustbar">
    <div class="vempra-shop-trustbar-inner">
      <?php echo implode( '<div class="vempra-shop-trustbar-divider"></div>', array_map( function ( $item ) {
          return '<div class="vempra-shop-trustbar-item">' . wp_kses_post( $item ) . '</div>';
      }, vempra_catalogo_confianza() ) ); ?>
    </div>
  </section>

  <section class="vempra-shop-section">

    <div class="vempra-shop-controls">
      <div class="vempra-shop-filters">
        <button type="button" class="vempra-shop-filter active" data-filter="all">Todos</button>
        <?php foreach ( vempra_catalogo_categorias() as $clave => $nombre ) : ?>
        <button type="button" class="vempra-shop-filter" data-filter="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $nombre ); ?></button>
        <?php endforeach; ?>
      </div>
      <div class="vempra-shop-sort" id="vempra-shop-sort-holder"></div>
    </div>

    <div class="vempra-shop-grid" id="vempra-shop-grid"><?php
      foreach ( $lista as $t ) { echo vempra_catalogo_tarjeta( $t ); }
    ?></div>

    <div class="vempra-shop-noresults" id="vempra-shop-noresults" style="display:none">
      <p><?php echo esc_html( $textos['sin_result'] ); ?></p>
    </div>

  </section>

  <section class="vempra-shop-help">
    <div class="vempra-shop-help-inner">
      <h3><?php echo esc_html( $textos['ayuda_h'] ); ?></h3>
      <p><?php echo esc_html( $textos['ayuda_p'] ); ?></p>
      <a href="<?php echo esc_url( 'https://wa.me/' . $wa . '?text=' . rawurlencode( $textos['ayuda_msg'] ) ); ?>" target="_blank" rel="noopener" class="vempra-shop-help-btn"><?php echo esc_html( $textos['ayuda_btn'] ); ?></a>
    </div>
  </section>

</div>
	<?php
	return ob_get_clean();
} );

/**
 * Mientras la pagina Tours conserve el bloque HTML viejo, se dibuja el
 * shortcode en su lugar. Corre antes que inc/tarjetas.php (prioridad 20),
 * que de todos modos ignora el HTML del catalogo.
 */
function vempra_pagina_tours() {
	return (int) apply_filters( 'vempra_pagina_tours', 331 );
}

add_filter( 'the_content', function ( $content ) {
	if ( ! is_page( vempra_pagina_tours() ) || ! in_the_loop() || ! is_main_query() ) { return $content; }
	if ( has_shortcode( $content, 'vempra_tours' ) ) { return $content; }
	if ( false === strpos( $content, 'vempra-shop-grid' ) ) { return $content; }
	return do_shortcode( '[vempra_tours]' );
}, 9 );
