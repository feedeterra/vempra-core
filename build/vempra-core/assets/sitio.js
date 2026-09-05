/* sitio.js — carga en todo el sitio. Snippet 10: slider de resenas (.vempra-slider, portada y tours). Snippets 31 y 32: acordeon de Preguntas frecuentes y copiar email + mapa de Contacto (solo actuan si encuentran sus elementos). */
document.addEventListener('DOMContentLoaded', function() {
        const sliders = document.querySelectorAll('.vempra-slider');
        sliders.forEach(slider => {
            const slides = slider.querySelectorAll('.vempra-slide');
            const dotsContainer = slider.querySelector('.vempra-slider-dots');
            const prevBtn = slider.querySelector('.vempra-slider-prev');
            const nextBtn = slider.querySelector('.vempra-slider-next');
            if (!slides.length) return;

            let current = 0;
            let autoplayId = null;
            const interval = 6000;

            // Crear dots
            slides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.className = 'vempra-slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Reseña ' + (i + 1));
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            });

            function goTo(idx) {
                slides[current].classList.remove('active');
                slider.querySelectorAll('.vempra-slider-dot')[current].classList.remove('active');
                current = (idx + slides.length) % slides.length;
                slides[current].classList.add('active');
                slider.querySelectorAll('.vempra-slider-dot')[current].classList.add('active');
            }

            function next() { goTo(current + 1); }
            function prev() { goTo(current - 1); }

            if (prevBtn) prevBtn.addEventListener('click', prev);
            if (nextBtn) nextBtn.addEventListener('click', next);

            function startAutoplay() {
                stopAutoplay();
                autoplayId = setInterval(next, interval);
            }
            function stopAutoplay() {
                if (autoplayId) { clearInterval(autoplayId); autoplayId = null; }
            }

            slider.addEventListener('mouseenter', stopAutoplay);
            slider.addEventListener('mouseleave', startAutoplay);

            // Swipe táctil mobile
            let touchStart = 0;
            slider.addEventListener('touchstart', e => { touchStart = e.touches[0].clientX; }, {passive: true});
            slider.addEventListener('touchend', e => {
                const diff = touchStart - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) { diff > 0 ? next() : prev(); }
            });

            startAutoplay();
        });
    });

/* ===== snippet 31 ===== */
document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.vempra-faq-wrap details.vempra-faq-item').forEach(function(item) {
        item.addEventListener('toggle', function() {
          this.classList.toggle('open', this.hasAttribute('open'));
        });
      });
    });

/* ===== snippet 32 ===== */
document.addEventListener('DOMContentLoaded', function() {

      // === Copy email to clipboard ===
      document.querySelectorAll('[data-copy-email]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const email = this.getAttribute('data-copy-email');
          const original = this.textContent;
          const done = () => {
            this.textContent = '¡Copiado!';
            setTimeout(() => { this.textContent = original; }, 2000);
          };
          if (navigator.clipboard) {
            navigator.clipboard.writeText(email).then(done).catch(() => {
              const ta = document.createElement('textarea');
              ta.value = email;
              document.body.appendChild(ta);
              ta.select();
              document.execCommand('copy');
              document.body.removeChild(ta);
              done();
            });
          } else {
            const ta = document.createElement('textarea');
            ta.value = email;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            done();
          }
        });
      });

// === Inyectar mapa interactivo Google Maps ===
      const mapDiv = document.querySelector('.vempra-contact-mapa');
      if (mapDiv) {
        const iframe = document.createElement('iframe');
        iframe.src = 'https://www.google.com/maps/embed/v1/place?key=AIzaSyDKJPA1fhyA1Q1KdrpARX_5SfqYWLA_Cjo&q=Gutierrez+88+Mendoza+Argentina&zoom=16';
        iframe.title = 'Ubicación de Vempra Turismo · Gutiérrez 88, Mendoza';
        iframe.loading = 'lazy';
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
        iframe.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;border:0;z-index:3;display:block;';
        mapDiv.appendChild(iframe);
      }
    });
