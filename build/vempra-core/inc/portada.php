<?php
/**
 * PORTADA: lo que la home tiene ademas de su contenido.
 *
 *   - Shortcode [vempra_featured_tours]: las cuatro tarjetas destacadas,
 *     con el precio real del producto y la cuenta real de tours   (snippet 34)
 *   - Barra fija de arriba "10% OFF con el cupon NIEVE2026"       (snippet 42)
 *   - Banner "Temporada de nieve" antes de las categorias         (snippet 54)
 *
 * Los dos banners de nieve se prenden y apagan desde Vempra > Precios
 * (opcion vempra_nieve, prendida por defecto). Cuando se apagan no queda
 * nada: ni la barra, ni el CSS que corre la cabecera 46 px para hacerle
 * lugar.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function vempra_nieve_activa() {
	return 'no' !== get_option( 'vempra_nieve', 'si' );
}

add_action( 'admin_init', function () {
	register_setting( 'vempra_core', 'vempra_nieve', array(
		'type'              => 'string',
		'default'           => 'si',
		'sanitize_callback' => function ( $v ) { return 'no' === $v ? 'no' : 'si'; },
	) );
} );

/**
 * Los tours destacados de la portada. La descripcion corta y la duracion se
 * escriben aca; el titulo, la foto, el enlace y el precio salen del tour y de
 * su producto, asi que cambiar un precio en WooCommerce cambia la portada.
 */
function vempra_tours_destacados() {
	return (array) apply_filters( 'vempra_tours_destacados', array(
		array(
			'id'       => 483,
			'duration' => '12 hs · Full Day',
			'number'   => 'Nº01 — Más vendido',
			'category' => 'Panorámico · Cordillera',
			'desc'     => 'Recorrido por la Ruta 7 hasta el Mirador del Aconcagua, a 3.200 metros. Paradas en Potrerillos, Uspallata, Puente del Inca y Penitentes.',
			'cuotas'   => '3 cuotas sin interés',
			'feature'  => true,
		),
		array(
			'id'       => 526,
			'duration' => '9 hs · Full Day',
			'category' => 'Wine Tour',
			'short'    => 'Maipú',
			'desc'     => '3 bodegas históricas, almuerzo de 4 pasos con vino libre y cata de aceite de oliva.',
		),
		array(
			'id'       => 534,
			'duration' => '6 hs · Atardecer',
			'category' => 'Cabalgata',
			'short'    => 'Sunset y Cena',
			'desc'     => 'Caballos al atardecer con cena bajo las estrellas y fogón.',
		),
		array(
			'id'       => 529,
			'duration' => '14 hs · Imperdible',
			'category' => 'Panorámico',
			'short'    => 'Cañón del Atuel',
			'desc'     => 'El gran cañón mendocino con miradores y Lago Valle Grande.',
		),
	) );
}

add_shortcode( 'vempra_featured_tours', function () {
	$cuantos = function_exists( 'vempra_cuantos_tours' ) ? vempra_cuantos_tours() : 0;
	ob_start();
	?>
	<section class="vempra-v2-featured">
	  <div class="vempra-v2-featured-header">
	    <div class="vempra-v2-featured-left">
	      <div class="vempra-v2-featured-eyebrow">Selección <?php echo esc_html( date_i18n( 'Y' ) ); ?></div>
	      <h2 class="vempra-v2-featured-title">Las experiencias que <em>los viajeros más eligen.</em></h2>
	    </div>
	    <p class="vempra-v2-featured-intro">Tours seleccionados por el equipo. Confirmación inmediata, cancelación gratis hasta 72 hs antes, pago en 3 cuotas sin interés.</p>
	  </div>
	  <div class="vempra-v2-featured-grid">
	    <?php foreach ( vempra_tours_destacados() as $t ) :
	        if ( ! get_post( $t['id'] ) ) { continue; }
	        $img        = get_the_post_thumbnail_url( $t['id'], 'large' );
	        $title      = ! empty( $t['short'] ) ? $t['short'] : get_the_title( $t['id'] );
	        $url        = get_permalink( $t['id'] );
	        $precio     = function_exists( 'vempra_precio_de_tour' ) ? vempra_precio_de_tour( $t['id'] ) : 0;
	        $card_class = 'vempra-v2-tour-card' . ( ! empty( $t['feature'] ) ? ' vempra-v2-tour-feature' : '' );
	        $img_style  = $img ? "background-image:url('" . esc_url( $img ) . "')" : '';
	    ?>
	    <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $card_class ); ?>">
	      <div class="vempra-v2-tour-image" style="<?php echo esc_attr( $img_style ); ?>">
	        <span class="vempra-v2-tour-duration"><?php echo esc_html( $t['duration'] ); ?></span>
	        <?php if ( ! empty( $t['number'] ) ) : ?>
	        <span class="vempra-v2-tour-number"><?php echo esc_html( $t['number'] ); ?></span>
	        <?php endif; ?>
	      </div>
	      <div class="vempra-v2-tour-body">
	        <div class="vempra-v2-tour-category"><?php echo esc_html( $t['category'] ); ?></div>
	        <h3 class="vempra-v2-tour-title"><?php echo esc_html( $title ); ?></h3>
	        <p class="vempra-v2-tour-desc"><?php echo esc_html( $t['desc'] ); ?></p>
	        <div class="vempra-v2-tour-meta">
	          <div class="vempra-v2-tour-price">
	            <?php if ( $precio > 0 ) : ?>
	            <span class="vempra-v2-tour-price-label"><?php echo ! empty( $t['feature'] ) ? 'Desde, por persona' : 'Desde'; ?></span>
	            <span class="vempra-v2-tour-price-value"><?php echo esc_html( vempra_precio_en_pesos( $precio ) ); ?></span>
	            <?php endif; ?>
	            <?php if ( ! empty( $t['cuotas'] ) ) : ?>
	            <span class="vempra-v2-tour-price-cuotas"><?php echo esc_html( $t['cuotas'] ); ?></span>
	            <?php endif; ?>
	          </div>
	          <span class="vempra-v2-tour-arrow">→</span>
	        </div>
	      </div>
	    </a>
	    <?php endforeach; ?>
	  </div>
	  <div class="vempra-v2-featured-cta">
	    <a href="/tours-en-mendoza/" class="vempra-v2-btn-link-dark">Ver <?php echo $cuantos ? 'los ' . (int) $cuantos . ' tours' : 'todos los tours'; ?> disponibles →</a>
	  </div>
	</section>
	<?php
	return ob_get_clean();
} );

// ---------------------------------------------------------------------------
// Barra fija "10% OFF con el cupon NIEVE2026" en todo el sitio.
// ---------------------------------------------------------------------------
add_action( 'wp_footer', function () {
	if ( is_admin() || ! vempra_nieve_activa() ) { return; }
	?>
	<div id="vempra-promo-banner">
	    <div class="vempra-promo-content">
	        <span class="vempra-promo-emoji">❄</span>
	        <span class="vempra-promo-text">
	            <strong>10% OFF con el cupón</strong>
	            <span class="vempra-promo-mid">en toda la tienda</span>
	            <span class="vempra-promo-code">NIEVE2026</span>
	            <span class="vempra-promo-hint">— aplicalo al finalizar tu compra</span>
	        </span>
	    </div>
	</div>
	<style id="vempra-promo-css">
	#vempra-promo-banner{position:fixed;top:0;left:0;right:0;z-index:99999;background:#5B0B2B;color:#F5F0E6;font-family:'Inter Tight',sans-serif;font-size:14px;padding:10px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.2)}
	body.admin-bar #vempra-promo-banner{top:32px}
	.vempra-promo-content{max-width:1200px;margin:0 auto;text-align:center;line-height:1.4}
	.vempra-promo-emoji{margin-right:8px;font-size:16px}
	.vempra-promo-code{background:#B85C38;color:#fff;padding:3px 12px;border-radius:3px;margin:0 6px;letter-spacing:1px;font-family:'Bodoni Moda',Georgia,serif;font-weight:500;display:inline-block}
	.vempra-promo-hint{opacity:.85}
	.header_style_wrapper,#header,.header_wrapper,#main_menu,.tg-fixed-menu,.tg-smart-fixed-menu{top:46px !important}
	body.admin-bar .header_style_wrapper,body.admin-bar #header,body.admin-bar .header_wrapper,body.admin-bar #main_menu{top:78px !important}
	#wrapper{padding-top:120px !important}
	@media (max-width:768px){
	  #vempra-promo-banner{font-size:12px;padding:9px 12px}
	  .vempra-promo-content{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
	  .vempra-promo-mid,.vempra-promo-hint{display:none}
	  .vempra-promo-code{margin:0 0 0 6px}
	  body.admin-bar #vempra-promo-banner{top:46px}
	  .header_style_wrapper,#header,.header_wrapper,#main_menu{top:44px !important}
	  #wrapper{padding-top:118px !important}
	}
	</style>
	<?php
}, 5 );

// ---------------------------------------------------------------------------
// Banner "Temporada de nieve" en la portada, antes de las categorias.
// ---------------------------------------------------------------------------
add_filter( 'the_content', function ( $content ) {
	if ( ! vempra_nieve_activa() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) { return $content; }
	$marker = '<section class="vempra-v2-categories">';
	if ( false === strpos( $content, $marker ) ) { return $content; }

	$url = esc_url( home_url( '/tours-en-mendoza/?filter=nieve' ) );

	$banner  = '<section class="vempra-nieve-banner"><div class="vnb-inner">';
	$banner .= '<div class="vnb-text">';
	$banner .= '<span class="vnb-eyebrow">❄ Temporada de nieve ' . esc_html( date_i18n( 'Y' ) ) . '</span>';
	$banner .= '<h2 class="vnb-title">Consultá por tu día de nieve</h2>';
	// El texto acompaña el momento de la temporada (junio a septiembre):
	// al principio "se viene", en agosto y septiembre "últimas semanas".
	$mes = (int) current_time( 'n' );
	$sub = ( $mes >= 8 ) ? 'Últimas semanas de la temporada blanca en Mendoza. Asegurá tu salida a la montaña antes de que cierre.' : 'Se viene la temporada blanca en Mendoza. Asegurá tu salida a la montaña antes de que se llenen los cupos.';
	$banner .= '<p class="vnb-sub">' . esc_html( $sub ) . '</p>';
	$banner .= '<a class="vnb-cta" href="' . $url . '">Ver tours de nieve</a>';
	$banner .= '</div>';
	$banner .= '<div class="vnb-photo" role="img" aria-label="Padre e hijo esquiando en la nieve de la montaña de Mendoza"></div>';
	$banner .= '</div></section>';

	return str_replace( $marker, $banner . $marker, $content );
}, 20 );

// ---------------------------------------------------------------------------
// Los "Desde $X" de las seis tarjetas de categoria.
// ---------------------------------------------------------------------------

/**
 * El precio del tour mas barato de una categoria del catalogo.
 *
 * Devuelve 0 cuando ninguna tarjeta de esa categoria tiene precio todavia
 * (sin producto asociado o sin tarifas cargadas).
 */
function vempra_precio_desde_categoria( $slug ) {
	if ( ! function_exists( 'vempra_catalogo_tours' ) || ! function_exists( 'vempra_precio_de_tour' ) ) { return 0.0; }

	$minimo = 0.0;

	foreach ( vempra_catalogo_tours() as $t ) {
		if ( empty( $t['id'] ) || empty( $t['cats'] ) ) { continue; }
		// Un tour puede estar en dos categorias a la vez: el rafting con
		// termas figura como "aventura relax" y cuenta para las dos.
		if ( ! in_array( $slug, preg_split( '/\s+/', trim( (string) $t['cats'] ) ), true ) ) { continue; }

		$precio = (float) vempra_precio_de_tour( (int) $t['id'] );
		if ( $precio <= 0 ) { continue; }
		if ( $minimo <= 0 || $precio < $minimo ) { $minimo = $precio; }
	}

	return $minimo;
}

/**
 * Reescribe el "Desde $X" de cada tarjeta con el precio real.
 *
 * Los seis importes estan escritos a mano dentro del contenido de la portada,
 * asi que cada vez que cambiaba un precio en WooCommerce habia que acordarse
 * de entrar a editar la pagina; hoy los seis coinciden, pero es cuestion de
 * tiempo que dejen de hacerlo. Aca se recalculan solos a partir del catalogo.
 *
 * La categoria de cada tarjeta se saca de su propio enlace (?filter=slug) y
 * la expresion exige la clase de la tarjeta, asi no confunde ese enlace con
 * el "Ver tours de nieve" del banner, que apunta al mismo lugar. Ademas corre
 * en prioridad 10, antes de que el banner se agregue (prioridad 20).
 *
 * Si una categoria no tiene ningun precio se deja el numero que ya estaba:
 * es mejor un importe viejo que un "Desde $0".
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_front_page() || ! in_the_loop() || ! is_main_query() ) { return $content; }
	if ( false === strpos( $content, 'vempra-v2-cat-from' ) ) { return $content; }

	$nuevo = preg_replace_callback(
		'/\?filter=([a-z0-9-]+)"\s+class="vempra-v2-cat-card(.*?<div class="vempra-v2-cat-from">\s*Desde\s+)\$[\d.,]+/s',
		function ( $m ) {
			$precio = vempra_precio_desde_categoria( $m[1] );
			if ( $precio <= 0 ) { return $m[0]; }

			return '?filter=' . $m[1] . '" class="vempra-v2-cat-card' . $m[2] . vempra_precio_en_pesos( $precio );
		},
		$content
	);

	return is_string( $nuevo ) ? $nuevo : $content;
}, 10 );
