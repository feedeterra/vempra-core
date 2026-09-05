<?php
/**
 * SITIO: el pie de pagina y el boton flotante de WhatsApp.
 *
 *   - Pie propio de Vempra (marca, legajo, redes, newsletter que abre
 *     WhatsApp, asociaciones y legales). El pie del theme se oculta
 *     por CSS en assets/sitio.css                                 (snippets 15 y 44)
 *   - Boton flotante de WhatsApp abajo a la derecha                (snippet 16)
 *
 * Numero de WhatsApp y redes: se cambian aca, en un solo lugar.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function vempra_whatsapp() {
	return apply_filters( 'vempra_whatsapp', '5492616527094' );
}

add_action( 'wp_footer', function () {
	if ( is_admin() ) { return; }
	?>
<a href="https://wa.me/5492616527094?text=Hola%21%20Quiero%20consultar%20por%20un%20tour%20en%20Mendoza"
       target="_blank"
       rel="noopener"
       class="vempra-wa-floating"
       aria-label="Consultar por WhatsApp">
      <div class="vempra-wa-floating-tooltip">¿Te ayudamos?</div>
      <div class="vempra-wa-floating-icon">
        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
          <path fill="#fff" d="M16 0C7.16 0 0 7.16 0 16c0 2.82.74 5.47 2.02 7.77L0 32l8.46-2c2.2 1.2 4.7 1.88 7.4 1.88h.14C24.84 31.88 32 24.72 32 15.88c0-4.27-1.66-8.28-4.68-11.3C24.3 1.66 20.27 0 16 0zm0 29.32h-.12c-2.4 0-4.74-.65-6.78-1.88l-.48-.28-5.04 1.32 1.34-4.9-.32-.5C3.4 20.9 2.68 18.48 2.68 16c0-7.34 5.98-13.32 13.32-13.32 3.56 0 6.9 1.4 9.42 3.9s3.9 5.86 3.9 9.42c0 7.34-5.98 13.32-13.32 13.32zm7.3-9.96c-.4-.2-2.38-1.18-2.75-1.3-.37-.14-.64-.2-.9.2-.27.4-1.03 1.3-1.27 1.56-.23.27-.47.3-.87.1s-1.7-.62-3.24-2c-1.2-1.07-2.02-2.4-2.26-2.8-.23-.4-.02-.62.18-.82.18-.18.4-.47.6-.7.2-.23.27-.4.4-.66.13-.27.07-.5-.03-.7s-.9-2.16-1.23-2.96c-.32-.78-.65-.66-.9-.67h-.76c-.27 0-.7.1-1.07.5s-1.4 1.37-1.4 3.34c0 1.97 1.43 3.87 1.63 4.14.2.27 2.83 4.32 6.86 6.06.96.42 1.7.66 2.3.85.97.3 1.85.26 2.55.16.78-.12 2.38-.97 2.72-1.92.33-.94.33-1.75.23-1.92-.1-.17-.36-.27-.76-.46z"/>
        </svg>
      </div>
    </a>
	<?php
}, 99 );

add_action( 'wp_footer', function () {
	?>
<footer class="vempra-footer">

      <!-- ===== FILA SUPERIOR: Branding + Newsletter ===== -->
      <div class="vempra-footer-top">
        <div class="vempra-footer-container">

          <div class="vempra-footer-brand">
<div class="vempra-footer-wordmark">Vempra</div>
            <p class="vempra-footer-tagline">Inteligencia de viaje en Mendoza.<br>Operador local habilitado por el Ministerio de Turismo desde 2019.</p>
            <p class="vempra-footer-legajo"><strong>Legajo Min. Turismo Mendoza Nº 18414</strong></p>

            <div class="vempra-footer-social">
              <a href="https://instagram.com/vempra.mza" target="_blank" rel="noopener" aria-label="Instagram Vempra">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
              </a>
              <a href="https://www.facebook.com/p/Vempra-Mendoza-100089421825066/" target="_blank" rel="noopener" aria-label="Facebook Vempra">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a href="https://wa.me/5492616527094" target="_blank" rel="noopener" aria-label="WhatsApp Vempra">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              </a>
              <a href="https://vempra.tur.ar" target="_blank" rel="noopener" aria-label="Sitio oficial Vempra">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
              </a>
            </div>
          </div>

          <div class="vempra-footer-newsletter">
            <h4>Suscribite a nuestras novedades</h4>
            <p>Tours nuevos, ofertas exclusivas y guías de Mendoza directo a tu mail.</p>
            <form class="vempra-footer-newsletter-form" onsubmit="event.preventDefault(); var email=this.querySelector('input').value; if(email){ window.open('https://wa.me/5492616527094?text=Hola%21%20Quiero%20suscribirme%20a%20novedades%20de%20Vempra.%20Mi%20email%3A%20' + encodeURIComponent(email), '_blank'); this.reset(); }">
              <input type="email" placeholder="tu@email.com" required>
              <button type="submit">Suscribirme</button>
            </form>
            <p class="vempra-footer-newsletter-disclaimer">Sin spam. Cancelás cuando quieras.</p>
          </div>

        </div>

        <!-- BANDA DE ASOCIACIONES -->
        <div class="vempra-footer-asociaciones">
          <p class="vempra-footer-asoc-label">Miembros de</p>
          <div class="vempra-footer-asoc-logos">
            <div class="vempra-asoc-badge">
              <img src="https://tienda.vempra.tur.ar/wp-content/uploads/2026/05/mendoza.png" alt="Mendoza" loading="lazy">
            </div>
            <div class="vempra-asoc-badge">
              <img src="https://tienda.vempra.tur.ar/wp-content/uploads/2026/05/faevyt.png" alt="FAEVYT - Federación Argentina de Asociaciones de Empresas de Viajes y Turismo" loading="lazy">
            </div>
            <div class="vempra-asoc-badge">
              <img src="https://tienda.vempra.tur.ar/wp-content/uploads/2026/05/amavyt.png" alt="AMAVYT - Asociación Mendocina de Agencias de Viajes y Turismo" loading="lazy">
            </div>
            <a href="https://agenciasdeviajes.ar/#buscador" target="_blank" rel="noopener" class="vempra-asoc-badge vempra-asoc-badge-link" title="Verificar habilitación en RNAV - Buscar Legajo 18414">
              <img src="https://tienda.vempra.tur.ar/wp-content/uploads/2026/05/rnav.png" alt="RNAV - Registro Nacional de Agencias de Viajes" loading="lazy">
            </a>
          </div>
        </div>

      </div>

      <!-- ===== FILA CENTRAL: 4 columnas ===== -->
      <div class="vempra-footer-main">
        <div class="vempra-footer-container vempra-footer-columns">

          <div class="vempra-footer-col">
            <h4 class="vempra-footer-col-title">Tours</h4>
            <ul class="vempra-footer-list">
              <!-- FIXED: tour-category/X -> tours-en-mendoza/?filter=X -->
              <li><a href="/tours-en-mendoza/?filter=panoramicos">Panorámicos</a></li>
              <li><a href="/tours-en-mendoza/?filter=wine-tours">Wine Tours</a></li>
              <li><a href="/tours-en-mendoza/?filter=cabalgatas">Cabalgatas</a></li>
              <li><a href="/tours-en-mendoza/?filter=aventura">Aventura</a></li>
              <li><a href="/tours-en-mendoza/?filter=nieve">Nieve</a></li>
              <li><a href="/tours-en-mendoza/?filter=relax">Relax y Termas</a></li>
              <li><a href="/tours-en-mendoza/" class="vempra-footer-link-strong">Ver todos los tours →</a></li>
            </ul>
          </div>

          <div class="vempra-footer-col">
            <h4 class="vempra-footer-col-title">Información</h4>
            <ul class="vempra-footer-list">
              <li><a href="/nosotros/">Nosotros</a></li>
              <li><a href="/como-funciona/">Cómo funciona</a></li>
              <!-- FIXED: /faq/ -> /preguntas-frecuentes/ -->
              <li><a href="/preguntas-frecuentes/">Preguntas frecuentes</a></li>
              <li><a href="/contacto/">Contacto</a></li>
              <li><a href="/blog/">Blog</a></li>
            </ul>
          </div>

          <div class="vempra-footer-col">
            <h4 class="vempra-footer-col-title">Legal</h4>
            <ul class="vempra-footer-list">
              <li><a href="/terminos-y-condiciones/">Términos y Condiciones</a></li>
              <li><a href="/politica-de-privacidad/">Política de Privacidad</a></li>
              <li><a href="/politica-de-cancelaciones/">Política de Cancelaciones</a></li>
              <li><a href="/defensa-al-consumidor/">Defensa del Consumidor</a></li>
              <li><a href="/boton-de-arrepentimiento/">Botón de Arrepentimiento</a></li>
            </ul>

            <!-- QR LEGALES -->
            <div class="vempra-footer-qrs">
              <a href="https://www.argentina.gob.ar/produccion/defensadelconsumidor/formulario" target="_blank" rel="noopener" class="vempra-footer-qr-item" title="Defensa al Consumidor - Formulario oficial">
                <img src="https://tienda.vempra.tur.ar/wp-content/uploads/2026/05/defensa-al-consumidor.png" alt="QR Defensa al Consumidor" loading="lazy">
                <span>Defensa<br>Consumidor</span>
              </a>
              <!-- Placeholder para QR AFIP 960/D -->
              <div class="vempra-footer-qr-item vempra-footer-qr-placeholder" title="Próximamente">
                <div class="vempra-footer-qr-empty">QR</div>
                <span>AFIP<br>F.960/D</span>
              </div>
            </div>
          </div>

          <div class="vempra-footer-col">
            <h4 class="vempra-footer-col-title">Contacto</h4>
            <ul class="vempra-footer-list vempra-footer-contact-list">
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <a href="tel:+5492616527094">+54 9 261 652-7094</a>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <a href="mailto:info@vempra.tur.ar">info@vempra.tur.ar</a>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Gutiérrez 88, Piso 1, Of. 6<br>Mendoza, Argentina</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Lun-Vie: 9 a 20 hs<br>Sáb: 9 a 14 hs<br><strong style="color:#25D366;">WhatsApp 24 hs</strong></span>
              </li>
            </ul>
            <a href="https://wa.me/5492616527094?text=Hola%21%20Quiero%20consultar%20por%20un%20tour" target="_blank" rel="noopener" class="vempra-footer-wa-btn">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.88 11.89L4 20l4.22-1.11a7.93 7.93 0 0 0 3.79.97h.04a7.94 7.94 0 0 0 7.94-7.93 7.9 7.9 0 0 0-2.39-5.61z"/></svg>
              Hablanos por WhatsApp
            </a>
          </div>

        </div>
      </div>

      <!-- ===== FILA INFERIOR ===== -->
      <div class="vempra-footer-bottom">
        <div class="vempra-footer-container vempra-footer-bottom-inner">
          <div class="vempra-footer-copyright">
            © <?php echo date('Y'); ?> Vempra Turismo SAS · Todos los derechos reservados<br>
            <span class="vempra-footer-copyright-small">Legajo Ministerio de Turismo de Mendoza Nº 18414</span>
          </div>
          <div class="vempra-footer-payments">
            <span class="vempra-footer-payments-label">Aceptamos:</span>
            <div class="vempra-footer-payments-icons">
              <span class="vempra-pay-badge">Mercado Pago</span>
              <span class="vempra-pay-badge">Visa</span>
              <span class="vempra-pay-badge">Mastercard</span>
              <span class="vempra-pay-badge">Transferencia</span>
            </div>
          </div>
        </div>
      </div>

    </footer>
	<?php
}, 100 );
