/**
 * UX DE LA RESERVA
 *
 * Dos huecos que aparecieron en el QA de la ficha:
 *
 * 1. Agregar al carrito no confirma nada. El formulario de Bookings hace
 *    POST a la misma URL, WooCommerce agrega el item y la pagina se
 *    recarga arriba de todo. El aviso de "se agrego a tu carrito" no
 *    aparece por ningun lado: se comprobo que no esta ni en la ficha ni
 *    en el carrito, asi que no alcanza con imprimir wc_get_notices().
 *    Entonces la confirmacion se arma de este lado: antes de enviar se
 *    guarda el estado, y despues de la recarga se muestra solo si el
 *    contador del carrito efectivamente subio. Si el alta fallo, el
 *    contador no cambia y no se muestra nada.
 *
 * 2. Si falla el calculo del precio no se entiende que paso. Bookings
 *    recalcula por AJAX cada vez que cambia la fecha o la cantidad; si esa
 *    llamada se cae, el total queda viejo y el visitante no se entera.
 *    Aca se avisa y se ofrece reintentar.
 */
(function () {
	if (window.__vempraUX) { return; }
	window.__vempraUX = true;

	var LLAVE  = 'vempra_alta_carrito';
	var VENTANA = 120000; // 2 minutos: mas que eso no fue esta compra
	var ultimoConteo = -1;  // lo que el servidor dijo la ultima vez

	function $(sel, raiz) { return (raiz || document).querySelector(sel); }

	function el(tag, clase, texto) {
		var n = document.createElement(tag);
		if (clase) { n.className = clase; }
		if (texto !== undefined) { n.textContent = texto; }
		return n;
	}

	/**
	 * Cuantos items hay en el carrito, preguntandoselo al servidor.
	 *
	 * No se puede leer del globito de la cabecera: LiteSpeed sirve el HTML
	 * cacheado y ese numero queda viejo. Se comprobo en staging: el carrito
	 * tenia 4 y la pagina recien cargada mostraba 3. El endpoint de
	 * fragmentos de WooCommerce nunca se cachea, asi que es el unico que
	 * dice la verdad.
	 *
	 * Devuelve -1 si no se pudo averiguar.
	 */
	function contarEnElCarrito(listo) {
		var url = '/?wc-ajax=get_refreshed_fragments';
		fetch(url, { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (j) {
				if (!j || !j.fragments) { listo(-1); return; }
				var n = -1;
				for (var k in j.fragments) {
					if (!Object.prototype.hasOwnProperty.call(j.fragments, k)) { continue; }
					var doc = new DOMParser().parseFromString(j.fragments[k], 'text/html');
					var e = doc.querySelector('.cart_count, .xoo-wsc-items-count');
					if (!e) { continue; }
					var v = parseInt((e.textContent || '').replace(/[^0-9]/g, ''), 10);
					if (isFinite(v)) { n = v; break; }
				}
				listo(n);
			})
			.catch(function () { listo(-1); });
	}

	/**
	 * Corrige el globito de la cabecera.
	 *
	 * Mismo problema que arriba: en una pagina cacheada el visitante agrega
	 * un tour y el numero sigue diciendo lo de antes. Con esto queda bien
	 * en todas las paginas, no solo despues de reservar.
	 */
	function sincronizarGlobito(n) {
		if (n < 0) { return; }
		var globos = document.querySelectorAll('.cart_count, .xoo-wsc-items-count');
		for (var i = 0; i < globos.length; i++) {
			if (globos[i].textContent.trim() !== String(n)) { globos[i].textContent = String(n); }
		}
	}

	function guardado() {
		try {
			var s = sessionStorage.getItem(LLAVE);
			return s ? JSON.parse(s) : null;
		} catch (e) { return null; }
	}

	function borrar() {
		try { sessionStorage.removeItem(LLAVE); } catch (e) {}
	}

	/**
	 * WooCommerce refresca sus fragmentos solo, y ahi el contador del carrito
	 * lateral queda bien. Nos colgamos de ese refresco en vez de pedir lo
	 * mismo de nuevo. Si en un segundo y medio no paso nada —porque el
	 * refresco lo resolvio con lo que tenia guardado— preguntamos nosotros.
	 */
	function esperarAWooCommerce(listo) {
		function delDOM() {
			var e = $('.xoo-wsc-items-count');
			if (!e) { return -1; }
			var v = parseInt((e.textContent || '').replace(/[^0-9]/g, ''), 10);
			return isFinite(v) ? v : -1;
		}

		var contestado = false;
		function contestar(n) {
			if (contestado) { return; }
			contestado = true;
			listo(n);
		}

		if (window.jQuery) {
			window.jQuery(document.body).on(
				'wc_fragments_refreshed.vempra wc_fragments_loaded.vempra',
				function () { contestar(delDOM()); }
			);
		}

		setTimeout(function () {
			if (contestado) { return; }
			var n = delDOM();
			if (n >= 0) { contestar(n); return; }
			contarEnElCarrito(contestar);
		}, 1500);
	}

	/* ------------------------------------------------------------------ */
	/* 1. CONFIRMACION AL AGREGAR AL CARRITO                              */
	/* ------------------------------------------------------------------ */

	function anotarEnvio() {
		var form = $('form.cart');
		if (!form) { return; }
		if (form.vempraAnotado) { return; }
		form.vempraAnotado = true;

		form.addEventListener('submit', function () {
			var titulo = $('.single_tour_title') || $('h1');
			var costo  = $('.wc-bookings-booking-cost');
			var dato = {
				t: Date.now(),
				n: ultimoConteo,
				nombre: titulo ? titulo.textContent.trim() : '',
				total: costo ? costo.textContent.replace(/^[^$]*/, '').trim() : '',
				url: location.pathname
			};
			try { sessionStorage.setItem(LLAVE, JSON.stringify(dato)); } catch (e) {}
			vigilarAltaSinRecarga(dato.n);
		});
	}

	function confirmar() {
		var dato = guardado();
		if (!dato) { return; }

		// Vieja, de otra pagina, o el alta no prospero: se descarta sin ruido.
		if (Date.now() - dato.t > VENTANA) { borrar(); return; }
		if (dato.url !== location.pathname) { return; }

		contarEnElCarrito(function (ahora) {
			sincronizarGlobito(ahora);
			// Si el alta no prospero el numero no subio: no se muestra nada.
			// Si no se pudo averiguar, tampoco se inventa una confirmacion.
			if (ahora < 0) { borrar(); return; }
			if (dato.n >= 0 && ahora <= dato.n) { borrar(); return; }
			borrar();
			mostrarConfirmacion(dato);
		});
	}

	function mostrarConfirmacion(dato) {
		if ($('.vempra-confirma')) { return; }

		var caja = el('div', 'vempra-confirma');
		caja.setAttribute('role', 'status');
		caja.setAttribute('aria-live', 'polite');

		var cuerpo = el('div', 'vempra-confirma-cuerpo');
		cuerpo.appendChild(el('b', 'vempra-confirma-titulo', 'Listo, lo agregamos a tu carrito'));
		if (dato.nombre) { cuerpo.appendChild(el('span', 'vempra-confirma-tour', dato.nombre)); }
		if (dato.total)  { cuerpo.appendChild(el('span', 'vempra-confirma-total', dato.total)); }

		var acciones = el('div', 'vempra-confirma-acciones');
		var pagar = el('a', 'vempra-confirma-pagar', 'Ir a pagar');
		pagar.href = '/finalizar-compra/';
		var seguir = el('button', 'vempra-confirma-seguir', 'Seguir mirando');
		seguir.type = 'button';
		acciones.appendChild(pagar);
		acciones.appendChild(seguir);

		var cerrar = el('button', 'vempra-confirma-cerrar', '×');
		cerrar.type = 'button';
		cerrar.setAttribute('aria-label', 'Cerrar el aviso');

		caja.appendChild(cuerpo);
		caja.appendChild(acciones);
		caja.appendChild(cerrar);
		document.body.appendChild(caja);

		// El alta llega despues de una recarga completa, asi que el lector de
		// pantalla ya anuncio la pagina entera. Mover el foco al aviso es lo
		// unico que garantiza que se entere de que la reserva se agrego.
		pagar.focus();

		function tapar() {
			caja.classList.remove('vempra-confirma-visible');
			setTimeout(function () {
				if (caja.parentNode) { caja.parentNode.removeChild(caja); }
			}, 260);
		}

		seguir.addEventListener('click', tapar);
		cerrar.addEventListener('click', tapar);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { tapar(); }
		});

		requestAnimationFrame(function () {
			caja.classList.add('vempra-confirma-visible');
		});
	}

	/* ------------------------------------------------------------------ */
	/* 2. AVISO Y REINTENTO CUANDO NO SE PUEDE CALCULAR EL PRECIO         */
	/* ------------------------------------------------------------------ */

	function esCalculoDeBookings(ajustes) {
		if (!ajustes) { return false; }
		var d = ajustes.data;
		var u = ajustes.url || '';
		if (typeof d === 'string' && d.indexOf('wc_bookings_calculate_costs') !== -1) { return true; }
		return u.indexOf('wc_bookings_calculate_costs') !== -1;
	}

	function cajaDeError(form) {
		var previa = $('.vempra-precio-error', form);
		if (previa) { return previa; }

		var caja = el('div', 'vempra-precio-error');
		caja.setAttribute('role', 'alert');
		caja.appendChild(el('span', null, 'No pudimos calcular el precio. Puede ser la conexion.'));

		var boton = el('button', 'vempra-precio-reintentar', 'Reintentar');
		boton.type = 'button';
		boton.addEventListener('click', function () {
			boton.disabled = true;
			boton.textContent = 'Calculando…';
			// Bookings recalcula cuando el formulario cambia. Se dispara sobre
			// un campo real para que su propio listener lo tome.
			var campo = form.querySelector('[name^="wc_bookings_field"]') || form;
			campo.dispatchEvent(new Event('change', { bubbles: true }));
			setTimeout(function () {
				boton.disabled = false;
				boton.textContent = 'Reintentar';
			}, 4000);
		});
		caja.appendChild(boton);

		var costo = $('.wc-bookings-booking-cost', form);
		if (costo && costo.parentNode) {
			costo.parentNode.insertBefore(caja, costo);
		} else {
			form.appendChild(caja);
		}
		return caja;
	}

	function vigilarCalculo() {
		if (!window.jQuery) { return; }
		var form = $('form.cart .wc-bookings-booking-form') || $('form.cart');
		if (!form) { return; }

		window.jQuery(document).on('ajaxError.vempra', function (e, xhr, ajustes) {
			if (!esCalculoDeBookings(ajustes)) { return; }
			if (xhr && xhr.statusText === 'abort') { return; } // lo cancelo el propio Bookings
			cajaDeError(form).classList.add('vempra-precio-error-visible');
		});

		window.jQuery(document).on('ajaxSuccess.vempra', function (e, xhr, ajustes) {
			if (!esCalculoDeBookings(ajustes)) { return; }
			var caja = $('.vempra-precio-error', form);
			if (caja) { caja.classList.remove('vempra-precio-error-visible'); }
		});
	}

	/* ------------------------------------------------------------------ */

	/**
	 * Vigila el alta cuando la pagina NO se recarga.
	 *
	 * Se comprobo en staging: al enviar el formulario de Bookings el alta la
	 * resuelve el carrito lateral por AJAX y el documento nunca se vuelve a
	 * cargar (el contador pasa de 0 a 1 sin recarga). La confirmacion que
	 * esperaba a la recarga entonces no aparecia nunca. Aca se le pregunta
	 * al servidor hasta que el numero suba y se muestra el aviso en el acto.
	 *
	 * Si la pagina si se recarga, estos temporizadores mueren con ella y el
	 * aviso lo muestra confirmar() en la carga siguiente: nunca hay dos.
	 *
	 * Con un conteo previo desconocido (-1) no se hace nada, porque
	 * cualquier numero seria "mayor" y saldria un aviso falso.
	 */
	function vigilarAltaSinRecarga(antes) {
		if (!(antes >= 0)) { return; }
		var intentos = 0;

		function preguntar() {
			intentos++;
			contarEnElCarrito(function (ahora) {
				sincronizarGlobito(ahora);
				if (ahora > antes) {
					var dato = guardado();
					borrar();
					if (dato) { mostrarConfirmacion(dato); }
					return;
				}
				if (intentos < 10) { setTimeout(preguntar, 800); }
			});
		}

		setTimeout(preguntar, 700);
	}

	/**
	 * Mantiene el globito de la cabecera igual al del carrito lateral.
	 *
	 * El globito del tema (.cart_count) no viaja en los fragmentos de
	 * WooCommerce —se reviso el endpoint: estan el del carrito lateral y el
	 * del widget, ese no—, asi que WooCommerce lo refresca todo menos ese y
	 * queda con el numero viejo. En vez de leerlo una sola vez al arrancar,
	 * se lo copia del carrito lateral cada vez que algo cambia.
	 */
	var espejoPuesto = false;
	function espejoPermanente() {
		function copiar() {
			var origen = $('.xoo-wsc-items-count');
			if (!origen) { return; }
			var n = parseInt((origen.textContent || '').replace(/[^0-9]/g, ''), 10);
			if (!isFinite(n)) { return; }
			ultimoConteo = n;
			var globos = document.querySelectorAll('.cart_count');
			for (var i = 0; i < globos.length; i++) {
				if (globos[i].textContent.trim() !== String(n)) { globos[i].textContent = String(n); }
			}
		}

		copiar();
		if (espejoPuesto) { return; }
		espejoPuesto = true;

		if (window.jQuery) {
			window.jQuery(document.body).on(
				'wc_fragments_refreshed.vempraEspejo wc_fragments_loaded.vempraEspejo added_to_cart.vempraEspejo removed_from_cart.vempraEspejo',
				copiar
			);
		}
		if (window.MutationObserver) {
			new MutationObserver(copiar).observe(
				document.body,
				{ childList: true, subtree: true, characterData: true }
			);
		}
	}

	var yaPregunte = false;

	function arrancar() {
		anotarEnvio();
		vigilarCalculo();
		espejoPermanente();

		// Una sola consulta por carga: sirve para corregir el globito de la
		// cabecera y para saber cuantos items habia antes de este alta.
		if (yaPregunte) { return; }
		yaPregunte = true;

		function recibir(n) {
			// Si no se pudo averiguar, se libera la marca para que el
			// segundo pase vuelva a intentar: antes se perdia el unico tiro.
			if (n < 0) { yaPregunte = false; return; }
			ultimoConteo = n;
			sincronizarGlobito(n);
			confirmar();
		}

		// Si venimos de agregar algo, el numero tiene que ser exacto: se
		// pregunta al servidor. Si no, alcanza con lo que ya trae
		// WooCommerce en su propio refresco de fragmentos, y nos ahorramos
		// un pedido en cada pagina.
		if (guardado()) { contarEnElCarrito(recibir); return; }
		esperarAWooCommerce(recibir);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', arrancar);
	} else {
		arrancar();
	}
	// El formulario de Bookings se termina de armar despues; el segundo pase
	// engancha el submit aunque en el primero todavia no existiera.
	setTimeout(arrancar, 1500);
})();
