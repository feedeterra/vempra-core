/* tour-extras.js — snippets 11 y 13 (badges + politica de cancelacion, FAQ de compra + schema FAQPage) y el respaldo del snippet 37 ("Coste de la reserva" -> "Valor"). Carga solo en las fichas de tour. */

/* ===== snippet 11: Vempra - Badges de confianza + política cancelación en sidebar Tour ===== */
document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.single_tour_booking_wrapper');
        if (!sidebar) return;

        const trustBlock = document.createElement('div');
        trustBlock.className = 'vempra-trust-block';
        trustBlock.innerHTML = `
            <div class="vempra-trust-badges">
                <div class="vempra-trust-badge"><span class="vempra-trust-check">✓</span> Confirmación inmediata</div>
                <div class="vempra-trust-badge"><span class="vempra-trust-check">✓</span> Pago seguro online</div>
                <div class="vempra-trust-badge"><span class="vempra-trust-check">✓</span> Sin recargos ocultos</div>
            </div>
            <div class="vempra-cancellation-policy">
                <div class="vempra-cancellation-icon">🛡️</div>
                <div class="vempra-cancellation-text">
                    <strong>Cancelación gratis</strong>
                    <span>hasta 72 hs antes de la salida</span>
                </div>
            </div>
        `;

        sidebar.appendChild(trustBlock);
    });

/* ===== snippet 13: Vempra - FAQ compra sidebar + Schema FAQPage + JS acordeón ===== */
document.addEventListener('DOMContentLoaded', function() {

        // === 1. FAQ DE COMPRA EN SIDEBAR ===
        const sidebar = document.querySelector('.single_tour_booking_wrapper');
        if (sidebar && !sidebar.querySelector('.vempra-faq-compra')) {
            const faqCompra = document.createElement('div');
            faqCompra.className = 'vempra-faq-compra';
            faqCompra.innerHTML = `
                <h4 class="vempra-faq-compra-title">Preguntas frecuentes</h4>
                <div class="vempra-faq-item">
                    <button type="button" class="vempra-faq-q">¿Cuándo recibo la confirmación?<span class="vempra-faq-arrow">+</span></button>
                    <div class="vempra-faq-a">Apenas se acredita el pago, te llega un email con el voucher y los detalles de la salida.</div>
                </div>
                <div class="vempra-faq-item">
                    <button type="button" class="vempra-faq-q">¿Cómo se paga la reserva?<span class="vempra-faq-arrow">+</span></button>
                    <div class="vempra-faq-a">Aceptamos Mercado Pago, tarjetas de crédito y débito hasta en 3 cuotas sin interés, transferencia o efectivo en oficina.</div>
                </div>
                <div class="vempra-faq-item">
                    <button type="button" class="vempra-faq-q">¿Puedo cancelar?<span class="vempra-faq-arrow">+</span></button>
                    <div class="vempra-faq-a">Sí. Cancelación gratis hasta 72 hs antes de la salida del tour.</div>
                </div>
                <div class="vempra-faq-item">
                    <button type="button" class="vempra-faq-q">¿Necesito traer el voucher impreso?<span class="vempra-faq-arrow">+</span></button>
                    <div class="vempra-faq-a">No, alcanza con mostrarlo desde el celular al guía que te pase a buscar.</div>
                </div>
                <div class="vempra-faq-item">
                    <button type="button" class="vempra-faq-q">¿Y si tengo otra duda?<span class="vempra-faq-arrow">+</span></button>
                    <div class="vempra-faq-a">Escribinos por WhatsApp al +54 9 261 652 7094 o a info@vempra.tur.ar — respondemos en menos de 24h.</div>
                </div>
            `;
            sidebar.appendChild(faqCompra);
        }

        document.querySelectorAll('.vempra-faq-q').forEach(b => {
            if (b.tagName === 'BUTTON') b.type = 'button';
        });

        // === 2. ACORDEÓN — con closest() para soportar <p> envoltorio ===
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.vempra-faq-q');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const item = btn.closest('.vempra-faq-item');
            if (!item) return;
            const isOpen = item.classList.contains('open');
            const seoParent = item.closest('.vempra-faq-seo');
            if (seoParent) {
                seoParent.querySelectorAll('.vempra-faq-item.open').forEach(i => {
                    if (i !== item) i.classList.remove('open');
                });
            }
            item.classList.toggle('open', !isOpen);
        }, true);

        // Abrir la primera del FAQ SEO por defecto
        const seoFirst = document.querySelector('.vempra-faq-seo .vempra-faq-item');
        if (seoFirst) seoFirst.classList.add('open');

        // === 3. "VER MÁS" ===
        document.querySelectorAll('.vempra-faq-seo').forEach(block => {
            const items = block.querySelectorAll('.vempra-faq-item');
            if (items.length <= 4) return;
            items.forEach((item, idx) => { if (idx >= 4) item.classList.add('vempra-faq-hidden'); });
            const hiddenCount = items.length - 4;
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'vempra-faq-toggle';
            toggleBtn.innerHTML = `Ver ${hiddenCount} preguntas más <span class="vempra-faq-toggle-arrow">▾</span>`;
            block.appendChild(toggleBtn);
        });

        document.addEventListener('click', function(e) {
            const tBtn = e.target.closest('.vempra-faq-toggle');
            if (!tBtn) return;
            e.preventDefault();
            e.stopPropagation();
            const block = tBtn.closest('.vempra-faq-seo');
            if (!block) return;
            const items = block.querySelectorAll('.vempra-faq-item');
            const hiddenCount = items.length - 4;
            const isExpanded = block.classList.toggle('vempra-faq-expanded');
            if (isExpanded) {
                tBtn.innerHTML = `Ver menos <span class="vempra-faq-toggle-arrow up">▾</span>`;
            } else {
                tBtn.innerHTML = `Ver ${hiddenCount} preguntas más <span class="vempra-faq-toggle-arrow">▾</span>`;
                block.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        }, true);

        // === 4. SCHEMA FAQPage JSON-LD ===
        const seoQuestions = document.querySelectorAll('.vempra-faq-seo .vempra-faq-item');
        if (seoQuestions.length) {
            const faqList = [];
            seoQuestions.forEach(item => {
                const q = item.querySelector('.vempra-faq-q');
                const a = item.querySelector('.vempra-faq-a');
                if (q && a) {
                    faqList.push({
                        "@type": "Question",
                        "name": q.textContent.replace('+', '').trim(),
                        "acceptedAnswer": {"@type": "Answer", "text": a.textContent.trim()}
                    });
                }
            });
            if (faqList.length) {
                const schema = {"@context": "https://schema.org", "@type": "FAQPage", "mainEntity": faqList};
                const script = document.createElement('script');
                script.type = 'application/ld+json';
                script.textContent = JSON.stringify(schema);
                document.head.appendChild(script);
            }
        }
    });

/* ===== snippet 37: respaldo en el DOM por si la traduccion no alcanza ===== */
document.addEventListener('DOMContentLoaded', function() {
        const replaceCostText = () => {
            const el = document.querySelector('.wc-bookings-booking-cost');
            if (el) {
                el.innerHTML = el.innerHTML
                    .replace(/Coste de la reserva/gi, 'Valor')
                    .replace(/Booking cost/gi, 'Valor');
            }
        };
        replaceCostText();
        const target = document.querySelector('.wc-bookings-booking-form');
        if (target) {
            const obs = new MutationObserver(replaceCostText);
            obs.observe(target, {childList: true, subtree: true, characterData: true});
        }
    });
