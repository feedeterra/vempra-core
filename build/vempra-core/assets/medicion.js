/**
 * MEDICION — EL VALOR DE add_to_cart EN LAS RESERVAS
 *
 * GTM4WP arma el evento add_to_cart leyendo la cantidad del formulario:
 *
 *   productdata.quantity = form.querySelector('[name=quantity]') && ... .value;
 *   value: productdata.price * productdata.quantity
 *
 * El formulario de WooCommerce Bookings no tiene campo quantity: la cantidad
 * son los pasajeros (wc_bookings_field_persons_*). Entonces quantity queda en
 * null, isNaN(null) es false asi que el guard del plugin no lo corrige, y el
 * value que llega a GA4 y a Meta es 0. El precio del item si viaja bien.
 *
 * Con value 0 la campana no puede optimizar por valor ni calcular ROAS.
 *
 * Se corrige interceptando dataLayer.push, no parcheando GTM4WP: asi la
 * correccion sobrevive a cualquier actualizacion del plugin y no depende del
 * orden en que se registran los listeners del click.
 *
 * El valor sale del total que ya muestra Bookings ("Total: $53.000"), que es
 * el que de verdad se agrega al carrito e incluye los distintos tipos de
 * pasajero. Si no se puede leer, se cae a precio x pasajeros.
 */
(function () {
	if (window.__vempraMedicion) { return; }
	window.__vempraMedicion = true;

	window.dataLayer = window.dataLayer || [];

	/**
	 * Convierte "$53.000" o "$ 53.000,00" en 53000.
	 *
	 * El ultimo separador decide: si lo siguen 3 digitos es separador de
	 * miles, si lo siguen 1 o 2 es el decimal. Asi funciona tanto con el
	 * formato argentino como con el ingles, sin depender de la configuracion
	 * de WooCommerce.
	 */
	function aNumero(texto) {
		var s = String(texto == null ? '' : texto).replace(/[^0-9.,]/g, '');
		if (!s) { return 0; }
		var corte = Math.max(s.lastIndexOf(','), s.lastIndexOf('.'));
		var enteros = s;
		var decimales = '';
		if (corte > -1) {
			var cola = s.slice(corte + 1);
			if (cola.length === 1 || cola.length === 2) {
				enteros   = s.slice(0, corte);
				decimales = cola;
			}
		}
		var n = parseFloat(enteros.replace(/[.,]/g, '') + (decimales ? '.' + decimales : ''));
		return isFinite(n) ? n : 0;
	}

	/**
	 * Total que calcula Bookings despues de elegir el dia. 0 si no hay.
	 *
	 * No se mira si la caja esta visible: en la ficha optimizada esta en
	 * display:none porque el total se muestra en la barra de reserva, pero
	 * Bookings le sigue escribiendo adentro el importe real en cada cambio.
	 */
	function totalDeLaReserva() {
		var caja = document.querySelector('form.cart .wc-bookings-booking-cost');
		if (!caja) { return 0; }
		var monto = caja.querySelector('.amount, .woocommerce-Price-amount, .price') || caja;
		return aNumero(monto.textContent);
	}

	/** Pasajeros elegidos, sumando todos los tipos. Al menos 1. */
	function pasajeros() {
		var campos = document.querySelectorAll('form.cart [name^="wc_bookings_field_persons"]');
		var total = 0;
		for (var i = 0; i < campos.length; i++) {
			var n = parseInt(campos[i].value, 10);
			if (isFinite(n) && n > 0) { total += n; }
		}
		return total > 0 ? total : 1;
	}

	function corregir(evento) {
		var ec = evento && evento.ecommerce;
		if (!ec || !ec.items || !ec.items.length) { return; }

		var personas = pasajeros();
		var suma = 0;
		for (var i = 0; i < ec.items.length; i++) {
			var item = ec.items[i];
			var cant = parseInt(item.quantity, 10);
			if (!isFinite(cant) || cant < 1) { cant = personas; item.quantity = cant; }
			suma += (parseFloat(item.price) || 0) * cant;
		}

		var valor = parseFloat(ec.value);
		if (isFinite(valor) && valor > 0) { return; }

		var total = totalDeLaReserva();
		ec.value = total > 0 ? total : suma;
	}

	/* ------------------------------------------------------------------ */
	/* VER EL TOUR: view_item y ViewContent                                 */
	/* ------------------------------------------------------------------ */

	/**
	 * La pagina del tour es un post del tipo `tour`, no un producto de
	 * WooCommerce. GTM4WP solo mira productos, asi que de toda la ficha no
	 * sale ningun evento: ni view_item para GA4 ni ViewContent para Meta.
	 * Sin eso no se puede armar publico de remarketing con quien miro un
	 * tour y no reservo, que es el publico mas barato que tiene el negocio.
	 *
	 * Los datos los publica el plugin en window.VEMPRA_ITEM (inc/frontend.php)
	 * con el id del producto, no el del post, para que view_item y
	 * add_to_cart hablen del mismo producto.
	 *
	 * El pixel de Meta lo carga un plugin de WordPress, no una etiqueta de
	 * GTM: en la ficha se ve el fbq('track','PageView') escrito en linea y
	 * ningun ViewContent. Por eso ViewContent se dispara aca directo, igual
	 * que el PageView. Si mas adelante se agrega una etiqueta de Meta dentro
	 * de GTM que escuche view_item, hay que sacar esta llamada o el evento
	 * se cuenta dos veces; el eventID que va abajo alcanza para que Meta lo
	 * deduplique mientras tanto.
	 */
	function verElTour() {
		if (window.__vempraViewItem) { return; }
		var item = window.VEMPRA_ITEM;
		if (!item || !item.item_id) { return; }
		window.__vempraViewItem = true;

		var moneda = item.currency || 'ARS';
		var valor  = parseFloat(item.price);
		if (!isFinite(valor) || valor < 0) { valor = 0; }

		window.dataLayer.push({
			event: 'view_item',
			ecommerce: {
				currency: moneda,
				value: valor,
				items: [{
					item_id: String(item.item_id),
					item_name: item.item_name || '',
					item_category: item.item_category || undefined,
					price: valor,
					quantity: 1
				}]
			}
		});

		if (typeof window.fbq !== 'function') { return; }
		window.fbq('track', 'ViewContent', {
			content_ids: [String(item.item_id)],
			content_type: 'product',
			content_name: item.item_name || '',
			content_category: item.item_category || '',
			value: valor,
			currency: moneda
		}, { eventID: 'vc-' + item.item_id + '-' + Math.floor(Date.now() / 1000) });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', verElTour);
	} else {
		verElTour();
	}

	var pushOriginal = window.dataLayer.push;
	window.dataLayer.push = function () {
		for (var i = 0; i < arguments.length; i++) {
			var evento = arguments[i];
			if (evento && (evento.event === 'add_to_cart' || evento.event === 'remove_from_cart')) {
				try { corregir(evento); } catch (e) {}
			}
		}
		return pushOriginal.apply(this, arguments);
	};
})();
