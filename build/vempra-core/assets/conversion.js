/**
 * CONVERSION
 *
 * Lo que la ficha del tour necesita del lado del navegador despues de la
 * auditoria de CRO:
 *
 *   M-01  la barra de reserva fija de celular se esconde mientras el
 *         formulario de reserva esta a la vista.
 *   M-02  el renglon de estado desaparece cuando Bookings termina de dibujar
 *         el formulario, y se convierte en una salida por WhatsApp si a los
 *         ocho segundos el formulario nunca aparecio.
 *   F-05  el minimo de personas se avisa mientras el visitante escribe, y no
 *         recien cuando aprieta Reservar.
 *   M-06  el calendario arranca plegado en celular y se pliega solo cuando ya
 *         hay fecha elegida.
 *   M-03  el respaldo se lee como acordeon y la galeria como carrusel.
 *
 * Todo lo que toca el formulario de Bookings espera a que Bookings exista:
 * el formulario sale del servidor oculto y lo dibuja su propio JavaScript,
 * asi que aca no se puede dar nada por hecho al cargar la pagina.
 */
(function () {
	'use strict';

	var CFG = window.VEMPRA_CONV || {};
	var CEL = 768;

	function listo(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function visible(el) {
		return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
	}

	/**
	 * Llama a fn() cuando cond() da verdadero, mirando cada 250 ms. Si a los
	 * limite milisegundos no paso, llama a fin(). Sirve para todo lo que
	 * depende de que Bookings ya haya dibujado algo.
	 */
	function esperar(cond, fn, limite, fin) {
		if (cond()) { fn(); return; }
		var pasado = 0;
		var t = setInterval(function () {
			pasado += 250;
			if (cond()) {
				clearInterval(t);
				fn();
			} else if (pasado >= limite) {
				clearInterval(t);
				if (fin) { fin(); }
			}
		}, 250);
	}

	// -----------------------------------------------------------------------
	// M-06 · Calendario plegado
	// -----------------------------------------------------------------------

	var calFieldset = null;

	function calAbierto() {
		return !!(calFieldset && calFieldset.classList.contains('vempra-cal-abierto'));
	}

	function calAbrir() {
		if (!calFieldset) { return; }
		calFieldset.classList.remove('vempra-cal-plegado');
		calFieldset.classList.add('vempra-cal-abierto');
		var b = calFieldset.querySelector('.vempra-cal-btn');
		if (b) { b.setAttribute('aria-expanded', 'true'); }
	}

	function calCerrar() {
		if (!calFieldset) { return; }
		calFieldset.classList.remove('vempra-cal-abierto');
		calFieldset.classList.add('vempra-cal-plegado');
		var b = calFieldset.querySelector('.vempra-cal-btn');
		if (b) { b.setAttribute('aria-expanded', 'false'); }
	}

	/**
	 * La fecha elegida, como DD/MM/AAAA, leida de los tres campitos que
	 * Bookings deja debajo del calendario. Cadena vacia si todavia no hay
	 * fecha o si los campos no se pudieron interpretar: en ese caso no se
	 * toca nada, que es lo seguro.
	 */
	function fechaElegida(fs) {
		var ins = fs.querySelectorAll('.wc-bookings-date-picker-date-fields input');
		var d = '', m = '', y = '';
		for (var i = 0; i < ins.length; i++) {
			var pista = (ins[i].name || '') + ' ' + (ins[i].className || '') + ' ' + (ins[i].id || '');
			var v = (ins[i].value || '').replace(/\D/g, '');
			if (!v) { continue; }
			if (/year|anio|ano/i.test(pista)) { y = v; }
			else if (/month|mes/i.test(pista)) { m = v; }
			else if (/day|dia/i.test(pista)) { d = v; }
		}
		if (!d || !m || !y) { return ''; }
		return ('0' + d).slice(-2) + '/' + ('0' + m).slice(-2) + '/' + y;
	}

	function calendario(form) {

		if (CFG.calendario === false) { return; }

		var fs = form.querySelector('.wc-bookings-date-picker');
		if (!fs) { return; }
		var picker = fs.querySelector('.picker');
		if (!picker) { return; }

		calFieldset = fs;

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'vempra-cal-btn';
		btn.setAttribute('aria-expanded', 'false');
		btn.innerHTML = '<span class="vempra-cal-texto">Elegí la fecha de salida</span>'
			+ '<span class="vempra-cal-flecha" aria-hidden="true">▼</span>';

		fs.insertBefore(btn, picker);
		calCerrar();

		btn.addEventListener('click', function () {
			if (calAbierto()) { calCerrar(); } else { calAbrir(); }
		});

		// Al elegir una fecha, el calendario se pliega solo y el boton pasa a
		// mostrarla: asi los campos de personas y el boton de reservar quedan
		// a la vista sin scrollear.
		picker.addEventListener('click', function () {
			setTimeout(function () {
				var f = fechaElegida(fs);
				if (!f) { return; }
				var t = btn.querySelector('.vempra-cal-texto');
				if (t) { t.textContent = 'Salida: ' + f; }
				calCerrar();
			}, 500);
		});
	}

	// -----------------------------------------------------------------------
	// F-05 · Minimo de personas
	// -----------------------------------------------------------------------

	function personas(form) {

		var cab = form.querySelector('.vempra-reserva-cabecera');
		var min = cab ? parseInt(cab.getAttribute('data-min-personas'), 10) : 1;
		if (!(min > 1)) { return; }

		var aviso = document.createElement('p');
		aviso.className = 'vempra-personas-aviso';
		aviso.setAttribute('aria-live', 'polite');

		function campos() {
			return form.querySelectorAll('input[name^="wc_bookings_field_persons_"]');
		}

		function revisar() {
			var c = campos();
			if (!c.length) { return; }

			if (!aviso.parentNode) {
				var ultimo = c[c.length - 1];
				var caja = ultimo.closest ? ultimo.closest('p') : ultimo.parentNode;
				if (!caja) { caja = ultimo.parentNode; }
				caja.parentNode.insertBefore(aviso, caja.nextSibling);
			}

			var suma = 0;
			for (var i = 0; i < c.length; i++) {
				suma += parseInt(c[i].value, 10) || 0;
			}

			if (suma < min) {
				var faltan = min - suma;
				aviso.textContent = 'Este tour sale con un mínimo de ' + min + ' personas. '
					+ 'Llevás ' + suma + ': falta ' + faltan
					+ (faltan === 1 ? ' persona' : ' personas')
					+ '. Pueden ser menores, cuenta el total del grupo.';
				aviso.classList.add('visible');
			} else {
				aviso.classList.remove('visible');
			}
		}

		function esDeGrupo(e) {
			return !!(e.target && e.target.name
				&& e.target.name.indexOf('wc_bookings_field_persons_') === 0);
		}

		form.addEventListener('input', function (e) { if (esDeGrupo(e)) { revisar(); } });
		form.addEventListener('change', function (e) { if (esDeGrupo(e)) { revisar(); } });

		esperar(function () { return campos().length > 0; }, revisar, 10000);
	}

	// -----------------------------------------------------------------------
	// M-02 · Estado del formulario mientras Bookings arranca
	// -----------------------------------------------------------------------

	function estado(form, alAparecer) {

		var caja  = form.querySelector('.wc-bookings-booking-form');
		var linea = form.querySelector('.vempra-reserva-estado');

		// Sin el renglon de estado no hay nada que sacar, pero igual hay que
		// avisar cuando el formulario aparece: de eso depende el calendario.
		if (!linea) {
			esperar(function () { return visible(caja); }, function () {
				if (alAparecer) { alAparecer(); }
			}, 8000);
			return;
		}

		esperar(
			function () { return visible(caja); },
			function () {
				if (linea.parentNode) { linea.parentNode.removeChild(linea); }
				if (alAparecer) { alAparecer(); }
			},
			8000,
			function () {
				var wa = linea.getAttribute('data-wa');
				linea.classList.add('vempra-reserva-falla');
				linea.innerHTML = wa
					? 'El calendario está tardando más de lo normal. '
						+ '<a href="' + wa + '" rel="nofollow noopener" target="_blank">'
						+ 'Escribinos por WhatsApp</a> y reservamos en el momento.'
					: 'El calendario está tardando más de lo normal. Actualizá la página, por favor.';
			}
		);
	}

	// -----------------------------------------------------------------------
	// M-01 · Barra de reserva fija
	// -----------------------------------------------------------------------

	function sticky(form) {

		var barra = document.getElementById('vempra-sticky');
		if (!barra) { return; }

		if (!form) {
			if (barra.parentNode) { barra.parentNode.removeChild(barra); }
			document.body.classList.add('vempra-sin-sticky');
			return;
		}

		var cta = barra.querySelector('.vempra-sticky-cta');
		if (cta) {
			cta.addEventListener('click', function () {
				calAbrir();
				var y = form.getBoundingClientRect().top + window.pageYOffset - 76;
				if (window.scrollTo && 'scrollBehavior' in document.documentElement.style) {
					window.scrollTo({ top: y, behavior: 'smooth' });
				} else {
					window.scrollTo(0, y);
				}
			});
		}

		function tapar(si) {
			if (si) {
				barra.classList.add('vempra-sticky-oculta');
				document.body.classList.add('vempra-sin-sticky');
			} else {
				barra.classList.remove('vempra-sticky-oculta');
				document.body.classList.remove('vempra-sin-sticky');
			}
		}

		if (window.IntersectionObserver) {
			new window.IntersectionObserver(function (e) {
				tapar(e[0].isIntersecting);
			}, { threshold: 0.2 }).observe(form);
			return;
		}

		// Navegador viejo: se mira la posicion al scrollear, sin observador.
		var pendiente = false;
		window.addEventListener('scroll', function () {
			if (pendiente) { return; }
			pendiente = true;
			window.requestAnimationFrame(function () {
				pendiente = false;
				var r = form.getBoundingClientRect();
				tapar(r.top < window.innerHeight * 0.8 && r.bottom > 0);
			});
		});
	}

	// -----------------------------------------------------------------------
	// M-03 · Respaldo en acordeon
	// -----------------------------------------------------------------------

	function respaldo() {

		var r = document.querySelector('.vempra-respaldo');
		if (!r || r.classList.contains('vempra-acordeon')) { return; }

		var h = r.querySelector('h2');
		if (!h || h.parentNode !== r) { return; }

		var cuerpo = document.createElement('div');
		cuerpo.className = 'vempra-acordeon-cuerpo';

		var n = h.nextSibling;
		while (n) {
			var sig = n.nextSibling;
			cuerpo.appendChild(n);
			n = sig;
		}
		r.appendChild(cuerpo);
		r.classList.add('vempra-acordeon');

		h.setAttribute('role', 'button');
		h.setAttribute('tabindex', '0');
		h.setAttribute('aria-expanded', 'false');

		function alternar() {
			var abierto = r.classList.toggle('vempra-abierto');
			h.setAttribute('aria-expanded', abierto ? 'true' : 'false');
		}

		h.addEventListener('click', alternar);
		h.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
				e.preventDefault();
				alternar();
			}
		});
	}

	// -----------------------------------------------------------------------
	// M-03 · Galeria en carrusel
	// -----------------------------------------------------------------------

	function galeria() {

		var g = document.querySelector('.vempra-galeria');
		if (!g || g.classList.contains('vempra-galeria-carrusel')) { return; }

		var grid = g.querySelector('.vempra-galeria-grid');
		if (!grid) { return; }

		var fotos = grid.querySelectorAll('.vempra-foto');
		if (fotos.length < 2) { return; }

		g.classList.add('vempra-galeria-carrusel');

		var cuenta = document.createElement('div');
		cuenta.className = 'vempra-galeria-cuenta';
		cuenta.textContent = '1 / ' + fotos.length;
		g.appendChild(cuenta);

		var pendiente = false;
		grid.addEventListener('scroll', function () {
			if (pendiente) { return; }
			pendiente = true;
			window.requestAnimationFrame(function () {
				pendiente = false;
				if (window.innerWidth > CEL) { return; }
				var ancho = grid.scrollWidth / fotos.length;
				var i = ancho > 0 ? Math.round(grid.scrollLeft / ancho) + 1 : 1;
				if (i < 1) { i = 1; }
				if (i > fotos.length) { i = fotos.length; }
				cuenta.textContent = i + ' / ' + fotos.length;
			});
		});
	}

	// -----------------------------------------------------------------------

	listo(function () {

		var form = document.querySelector('.vempra-booking-form');

		if (form) {
			// El calendario se arma recien cuando Bookings dibujo el
			// formulario: antes de eso lo que hay adentro todavia puede ser
			// reemplazado por el propio Bookings.
			estado(form, function () { calendario(form); });
			personas(form);
		}

		sticky(form);
		respaldo();
		galeria();
	});

}());
