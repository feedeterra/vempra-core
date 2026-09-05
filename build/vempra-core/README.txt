VEMPRA CORE — v1.10.0
=====================

Que hace
--------
Junta en archivos versionados las tres personalizaciones que hasta ahora
vivian sueltas dentro de la base de datos de WordPress.

1. PRECIO UNICO
   El precio de cada tour se lee siempre del producto de WooCommerce.
   Antes estaba escrito en tres lugares —el producto, el campo tour_price de
   la pagina, y un array dentro del JavaScript— y se desincronizaban solos.
   En el Tour Bodegas la pagina decia 55.000 y el producto 53.000.

   Cuando un producto no tiene precio en el campo de vitrina —son productos
   de WooCommerce Bookings con costo base 0 y el precio real cargado en los
   tipos de pasajero— se lee el costo de la tarifa mas cara, que es siempre
   la de adulto y es exactamente el numero que la ficha ya mostraba en la
   cabecera. Aparecen en el panel como "tarifa Adulto".

   Sin esto se colaba el valor viejo de tour_price en las tarjetas.

   El plugin nunca escribe precios, solo los lee. Todo se edita desde el
   producto de WooCommerce: el precio de vitrina en Datos del producto ->
   General, y las tarifas en Datos del producto -> Personas.

2. LOS ESTILOS
   assets/vempra.css. Salieron del post 22, donde 165 KB de CSS vivian como
   texto de una entrada de blog. Copiados tal cual, sin tocar una regla.
   Se cargan en todas las paginas, igual que antes.

3. EL JAVASCRIPT DE LA FICHA
   assets/vempra.js. Salio del contenido de la pagina del Tour Bodegas, donde
   estaba embebido como <script> en medio del texto. Por eso viene escrito sin
   && y sin comentarios: el editor de WordPress reescribia esos caracteres.

   Se carga solo en los tours listados en vempra_tours_optimizados()
   (inc/assets.php), que hoy es unicamente el Tour Bodegas (528), porque es el
   unico con el HTML que ese JavaScript espera. Para sumar otro se agrega su
   ID a esa lista, o desde afuera:

     add_filter( 'vempra_tours_optimizados', function ( $ids ) {
         $ids[] = 754;
         return $ids;
     } );


Instalacion
-----------
1. Plugins -> Anadir nuevo -> Subir plugin
2. Elegir el ZIP -> Instalar ahora
3. Si avisa que ya esta instalado: "Reemplazar el actual con el subido"
4. Activar
5. Menu lateral "Vempra" -> revisar la tabla de precios

Al instalarlo, el CSS y el JavaScript quedan cargados dos veces: la copia
vieja que sigue dentro de los posts, y la nueva del plugin. No se rompe nada.
El CSS repetido son las mismas reglas, y el JavaScript arranca con un
"if (window.__vempraFicha) { return; }" que apaga la segunda copia. Recien
despues de verificar que la version del plugin funciona se sacan las viejas.


Como volver atras
-----------------
Desde la v1.1.0 las copias viejas ya no estan en los posts: el <script> se
saco del contenido del tour 528 y los 165 KB de CSS se sacaron del post 22.
Asi que desactivar el plugin ahora deja la web sin esos estilos y sin el
JavaScript de la ficha. No se pierde ningun dato, pero se nota.

Para revertir de verdad hay que restaurar el backup de UpdraftPlus previo a
la v1.1.0.

Lo que si se puede apagar sin consecuencias es el precio unico: en el menu
"Vempra" hay una casilla para eso. Los precios vuelven a leerse como antes y
el resto del plugin sigue funcionando.


Que NO incluye
--------------
- El PHP que vive en el plugin Code Snippets, o sea en la base de datos y sin
  versionar. Ahi estan, entre otras cosas, los textos de cancelacion que
  todavia dicen 24 horas cuando la politica real es 72 hs.
- Los campos del tour en el panel.
- Google Tag Manager.


Archivos
--------
vempra-core.php      arranque y constantes
inc/precios.php      la logica del precio unico
inc/tarjetas.php     el precio real en las tarjetas de la pagina de tours
inc/accesibilidad.php un solo H1, nombres accesibles y separadores de precio
inc/actualizador.php  se actualiza solo desde GitHub
inc/admin.php        la pantalla "Vempra" del panel
inc/assets.php       carga del CSS y del JavaScript
assets/vempra.css    los estilos, ex post 22
assets/vempra.js     el JavaScript de la ficha, ex contenido del tour 528
assets/medicion.js   corrige el valor que se le informa a GTM y a Meta


Novedades de la v1.2.0
----------------------
- Precio unico: los 7 tours sin precio de vitrina ahora muestran el costo
  real del producto de WooCommerce.
- En movil se saca el carrito flotante de la ficha del tour: duplicaba al
  carrito del encabezado y tapaba texto. WhatsApp baja al hueco que deja.
  (Si esta el mismo bloque cargado a mano en Apariencia > Personalizar >
  CSS adicional, se puede borrar de ahi: ya viene en el plugin.)
- add_to_cart informaba valor 0. GTM4WP calcula el valor multiplicando el
  precio por el campo "quantity" del formulario, y el formulario de reservas
  no tiene ese campo: la cantidad son los pasajeros. Ahora el evento viaja
  con el total real de la reserva y con la cantidad de pasajeros.


NOVEDADES v1.10.0

La pagina Tours (/tours-en-mendoza/, pagina 331) ya no es HTML escrito a
mano: la arma el shortcode [vempra_tours] desde inc/catalogo.php. Se ve
identica, pero ahora:

- El precio de cada tarjeta sale del producto de WooCommerce (el mismo
  precio que la ficha y la portada). Cambiar un precio cambia las tres.
- El contador "+N experiencias disponibles" cuenta los tours publicados.
- Un tour despublicado desaparece de la grilla solo.
- Un tour nuevo que no este en la lista aparece igual al final, con su
  titulo, extracto y foto destacada.

Como se edita:
- Textos (titulo, subtitulo, ayuda, boton): vempra_catalogo_textos().
- Categorias de los filtros: vempra_catalogo_categorias().
- Sellos de confianza de arriba: vempra_catalogo_confianza().
- Las tarjetas y su orden: vempra_catalogo_tours(). Cada una tiene id del
  tour, titulo, categoria de filtro (cats), etiqueta (categoria), texto
  corto (desc), datos (meta), foto (clase vempra-tour-bg-* del CSS) y
  opcionales badge, feature, cuotas y nombre (para el orden A-Z).
- Para sumar un tour: copiar un bloque, cambiar el id y los textos.

Mientras la pagina 331 conserve el HTML viejo, el plugin lo ignora y
muestra el shortcode; cuando su contenido sea solo [vempra_tours] pasa lo
mismo. El JavaScript de filtros y orden (assets/tienda.js) es el de siempre.


NOVEDADES v1.9.0

Todo lo que estaba en Code Snippets pasa al plugin. Quedan 0 snippets
activos; el sitio se ve igual, pero ahora cada pieza tiene un archivo con
nombre, esta versionada en GitHub y se cambia editando texto, no pegando
codigo en el panel.

Donde quedo cada cosa:
- inc/sitio.php      pie de pagina propio y boton flotante de WhatsApp.
- inc/portada.php    las 4 tarjetas destacadas [vempra_featured_tours], la
                     barra "10% OFF NIEVE2026" y el banner de temporada de
                     nieve de la portada.
- inc/paginas.php    clase de las paginas legales y datos estructurados de
                     la guia del Tour Alta Montana.
- inc/tours.php      shortcode [vempra_booking_form], redireccion producto
                     -> tour, foto del tour copiada al producto, "Valor" en
                     vez de "Coste de la reserva", formulario arriba en
                     celular, LiteSpeed sin cache ni defer en tours.
- inc/tienda.php     carrito sin sugeridos, cupon por URL (?cupon=CODIGO),
                     checkout sin direccion, cartel del asesor, sellos,
                     cupon dentro del resumen, transferencia por defecto,
                     boton "Ir a pagar", cartel de gracias y el corte de
                     reservas.
- inc/snippets.php   apaga una sola vez los 53 snippets migrados.
- assets/sitio.css   todo el CSS de la portada, pie, paginas, legales, blog
                     (antes eran 28 snippets que se imprimian en cada
                     pagina; ahora es un archivo cacheado, con un comentario
                     por bloque diciendo de que snippet salio).
- assets/sitio.js, tienda.js, tour-extras.js, checkout.css, checkout.js.

Lo que cambia de verdad:
- Corte de reservas: ya no se puede reservar despues de las 18:00 del dia
  anterior a la salida (el snippet existia pero estaba apagado). La hora se
  calcula con la hora de salida de cada tour.
- Las tarjetas destacadas de la portada leen el precio real del producto de
  WooCommerce (antes tenian los precios escritos a mano: $86.000, $185.000,
  $212.000 y $102.000) y cuentan los tours publicados ("Ver los 17 tours")
  en vez de tener el 17 escrito.
- La politica de cancelacion dice 72 hs en todos lados. Los badges de la
  ficha y la FAQ de compra decian "24 horas".
- Los banners de nieve se prenden y apagan con un tilde en Vempra > Precios
  (vienen prendidos). Apagados no dejan rastro: ni la barra ni el espacio
  de 46 px que le hace lugar.
- Los snippets migrados quedan desactivados en Code Snippets, no borrados.
  Cuando se verifique que todo esta bien se pueden borrar desde ahi, junto
  con los que ya estaban apagados o eran de una sola vez (1 a 8, 35, 39, 40).


NOVEDADES v1.8.0

El plugin se actualiza solo. Es la ultima version que se instala subiendo
un ZIP a mano.

- El codigo vive en GitHub (feedeterra/vempra-core). Cada version nueva es
  un release con su ZIP adjunto.
- WordPress consulta GitHub como mucho cada 12 horas y, si hay una version
  mayor, muestra el aviso de actualizacion en Plugins como con cualquier
  plugin del repositorio oficial. Un clic y listo; o automatico, si se
  activan las actualizaciones automaticas en la fila del plugin.
- En la fila del plugin hay un enlace "Buscar actualizacion" para no esperar
  las 12 horas.
- Sin credenciales: el repositorio es publico y el plugin no contiene
  ninguna clave.


NOVEDADES v1.7.0

Lo que salio del repaso de la tienda en vivo y se puede resolver desde el
plugin. Ninguno cambia el diseno: se ve igual que hoy.

- Un solo H1 en la portada. El theme ya imprime un H1 con el titulo de la
  pagina ("Vempra Turismo · Tours en Mendoza", el que lleva las palabras
  clave) y el contenido abria otro con el titulo grande del hero. Con dos H1
  ninguno manda. El del hero pasa a H2. Se comprobo en la pagina en vivo que
  todos los estilos del hero apuntan a la clase .vempra-v2-hero-title y
  ninguno a la etiqueta h1, asi que no se mueve un pixel.

- Siete enlaces sin nombre accesible: el logo (dos versiones), la lupa del
  menu movil, los tres botones de cerrar y el "volver arriba". Todos llevan
  adentro un icono por CSS, sin texto, y un lector de pantalla los anunciaba
  como "enlace" a secas. Ahora tienen aria-label.

- "Share" pasa a "Compartir" en el panel de compartir de la ficha. El theme
  lo escribe a mano, asi que se cambia solo ese h2 y por coincidencia exacta:
  "Share" es una palabra corta que tambien aparece en clases y atributos.

- Separadores de precio de WooCommerce fijados desde el plugin: punto para
  los miles, coma para los decimales. Asi el formato deja de depender de un
  ajuste de la tienda que se puede tocar sin querer.
  OJO: el precio grande de la ficha (.single_tour_price) NO pasa por
  WooCommerce; lo imprime el theme con number_format() y separadores
  ingleses, fuera de todo filtro. Ese lo sigue corrigiendo el script de
  inc/frontend.php despues de dibujar.

- La ficha optimizada se reconoce sola. Antes habia que anotar el ID de cada
  tour en el plugin y volver a instalarlo; ahora vempra_ficha_optimizada()
  mira el contenido del tour y carga el JavaScript en cuanto encuentra las
  marcas de la ficha optimizada (vempra-quick / vempra-precio-ficha). La
  lista de IDs queda como excepcion. Consecuencia practica: replicar la
  ficha a los otros 16 tours ya no pide reinstalar nada.


NOVEDADES v1.6.1

Correccion de la 1.6.0, encontrada al verificarla en vivo. Las tarjetas
mostraban ".000" en lugar de "$95.000".

El precio se armaba con preg_replace() y el reemplazo se escribe como
texto: PHP leia "$95.000" como la retrorreferencia $95, que no existe en
ese patron, la borraba y dejaba ".000". El data-price salia bien porque es
un numero sin signo peso, asi que el precio correcto viajaba igual para el
JavaScript y para la medicion; lo que se veia mal era el numero impreso.

Ahora se usa preg_replace_callback(), que devuelve el texto tal cual.

NOVEDADES v1.6.0

Segunda mitad del precio unico: las tarjetas de la pagina "Tours en Mendoza".

Hasta ahora el precio unico llegaba hasta la ficha de cada tour. Las
tarjetas de la pagina de tours seguian con el precio escrito a mano dentro
del HTML del editor, y se desactualizaban solas: la pagina llego a tener
diez precios viejos al mismo tiempo, con la Cabalgata al Atardecer
anunciada a 212.000 cuando se cobraba 165.000.

a) inc/tarjetas.php. Al dibujar la pagina, cada tarjeta con la clase
   vempra-shop-card busca su tour por el slug del enlace y reemplaza el
   data-price y el precio visible por el precio real del producto de
   WooCommerce. El HTML guardado en el editor deja de mandar: se puede
   dejar como esta y no vuelve a hacer falta editarlo.

b) La cabecera de esa pagina ("+17 experiencias disponibles") pasa a
   contar los tours publicados, igual que el "Ver los N tours" del pie.
   Decia 17 con dieciocho tours publicados.

Se hace en PHP y no en JavaScript a proposito: el precio correcto viaja en
el HTML. Corregido en el navegador, el visitante veria el precio viejo un
instante y los buscadores —que no ejecutan el JavaScript— seguirian
indexando el numero viejo.

El filtro sale sin hacer nada en cualquier contenido que no tenga tarjetas,
que hoy es todo el sitio menos esa pagina. Se apaga con el mismo
interruptor del precio unico, en el menu del plugin.

NOVEDADES v1.3.0
================
Todo lo de la Fase 02 que se puede resolver desde el plugin.

a) CONFIRMACION AL AGREGAR AL CARRITO (assets/ux.js)
   Al tocar "Reservar ahora" no pasaba nada visible: el item entraba al
   carrito y el visitante se quedaba mirando la misma pantalla. Ahora aparece
   una barra abajo con el nombre del tour, el total y dos salidas: "Ir a
   pagar" y "Seguir mirando". En movil se levanta 86 px para no taparse con
   la barra flotante de reserva.
   Solo se muestra si el contador del carrito efectivamente subio, asi que si
   la carga falla no aparece un cartel mintiendo.

b) REINTENTO CUANDO FALLA EL PRECIO (assets/ux.js)
   Si el calculo de precio de Bookings se cae, antes quedaba el precio viejo
   en pantalla sin aviso. Ahora sale un mensaje con boton "Reintentar".

c) ZOOM CON LOS DEDOS (inc/frontend.php)
   El theme escribia maximum-scale=1 en el viewport, que le sacaba al
   visitante la posibilidad de agrandar la pantalla. Se corrige en el head.

d) ALT DEL LOGO (inc/frontend.php)
   El logo se imprimia sin texto alternativo.

e) "VER LOS N TOURS" DEL PIE (inc/frontend.php)
   El numero estaba escrito a mano y decia 17 habiendo 18. Ahora se calcula
   con la cantidad real de tours publicados y no se vuelve a desactualizar.
   Es un arreglo provisorio: cuando el PHP del snippet del pie pase al
   plugin, se hace del lado del servidor.

f) ALTURA DE LOS CAMPOS DEL CHECKOUT (assets/vempra.css)
   Estaban en 38 px. Pasan a 44 px, que es el minimo para tocar comodo en
   un telefono.

g) VIEW_ITEM / VIEWCONTENT EN LAS FICHAS (assets/medicion.js + frontend.php)
   La pagina del tour es un post del tipo `tour`, no un producto, asi que
   GTM4WP no publicaba nada: no habia view_item en Google ni ViewContent en
   Meta en ninguna ficha. Ahora se publican, con el id del producto de
   WooCommerce —el mismo que despues manda add_to_cart— para que las dos
   plataformas vean un solo producto y no dos.

   AVISO: el ViewContent se dispara directo con fbq() porque el pixel lo
   carga un plugin y no una etiqueta de GTM. Si algun dia se agrega en Tag
   Manager una etiqueta de Meta que escuche view_item, hay que sacar esa
   llamada directa de assets/medicion.js o el evento se cuenta dos veces.


NOVEDADES v1.3.1
================
Correcciones que salieron de probar la 1.3.0 en vivo.

h) EL NUMERO DEL CARRITO ESTABA MAL EN TODO EL SITIO
   El globito de la cabecera lo imprime el theme dentro del HTML, y ese
   HTML lo sirve LiteSpeed desde su cache. Se comprobo: con cuatro tours
   adentro del carrito, la portada mostraba 0. WooCommerce refresca su
   carrito lateral por su cuenta pero no toca ese numero.
   Ahora assets/ux.js lo corrige en todas las paginas.

   Por lo mismo, la confirmacion al agregar al carrito ya no compara el
   numero de la pantalla —que puede venir viejo del cache— sino el que
   devuelve el endpoint de fragmentos de WooCommerce, que nunca se cachea.
   Sin este cambio la confirmacion no aparecia nunca.

i) LA CATEGORIA ERA DISTINTA AL VER Y AL AGREGAR
   view_item mandaba la categoria del post del tour ("Wine Tours") y el
   add_to_cart de GTM4WP la del producto ("Panoramicos"): el mismo tour
   figuraba como dos productos distintos. Ahora las dos salen del producto.

j) La imagen grande de la galeria arrancaba sin alt.


PENDIENTE DEL LADO DEL SITIO (no lo puede arreglar el plugin)
=============================================================
Las URLs de los CSS y JS salen sin ?ver= —en toda la pagina no hay una
sola— y el servidor los manda con cache de 7 dias. Resultado: despues de
cada actualizacion, cualquiera que ya haya visitado el sitio sigue viendo
los archivos viejos durante una semana. Es lo que paso al instalar la
1.3.0: el archivo nuevo estaba en el servidor y el navegador seguia
usando el viejo.

Hay que apagar el "Remove Query Strings" (LiteSpeed Cache -> Page
Optimization -> Tuning) o el snippet que haga lo mismo, y purgar el cache.
Mientras eso siga asi, cada actualizacion del plugin llega tarde.

NOVEDADES v1.3.2
----------------
k) assets/.htaccess: los archivos js y css del plugin ahora se sirven con
   cache de 5 minutos en vez de 7 dias. Sin esto, cada actualizacion del
   plugin tardaba una semana en verse en el navegador de quien ya habia
   entrado al sitio. Se comprobo en staging: el CDN de Hostinger responde
   "cache-control: public, max-age=604800" y las URLs salen sin "?ver=".

   Nota: revisado en el panel, la opcion "Remove Query Strings" de
   LiteSpeed (Page Optimization > Tuning) YA estaba desactivada, y ninguno
   de los 65 snippets toca script_loader_src. El "?ver=" lo saca algo del
   lado del hosting, fuera de WordPress. Por eso se arregla por cabeceras
   y no por URL.

NOVEDADES v1.5.3
----------------
La version que va a produccion. Junta la traduccion, el arreglo del cache
de precios y el tour numero 18.

u) TRADUCCION (inc/textos.php, archivo nuevo). Treinta y nueve textos que el
   visitante veia en ingles pasan a espanol: los globitos del calendario
   ("This date is available", "This date is fully booked and unavailable"),
   los avisos que frenan la compra ("Date is required - please choose one
   above", "The minimum persons per group is %d"), las esperas y errores del
   calculo de precio, y la pagina 404 entera, que estaba integramente en
   ingles.

   Se hace con un filtro de gettext y no con Loco Translate a proposito:
   Loco escribe los .mo en wp-content/languages, que no esta versionado, y
   cualquier actualizacion o restauracion del backup se los lleva puestos.
   Aca las cadenas viajan dentro del plugin, asi que produccion recibe la
   traduccion en el mismo paquete que el resto.

   El filtro corre antes de que Bookings arme sus objetos de JavaScript, asi
   que la misma traduccion alcanza al PHP y al JS. Las cuatro cadenas que el
   theme escribe a mano (la 404 y "Read More") no pasan por gettext y se
   reemplazan desde el script del head, con el texto tomado del mismo mapa:
   la traduccion vive en un solo lugar.

v) EL CACHE DE PRECIOS SE INVALIDA CUANDO CAMBIA UN PRECIO. Era un bug real
   y se pago caro: el mapa de precios se guarda doce horas y solo se tiraba
   con save_post. Cambiar el precio desde la API de WooCommerce, desde un
   import o desde cualquier herramienta que escriba el metadato directo no
   dispara save_post. Paso el 4/9: se subio el Tour Bodegas a 55.000 y la
   ficha siguio mostrando 53.000 el resto del dia.

   Ahora el mapa se tira tambien al cambiar _price, _regular_price,
   _sale_price, el costo de un tipo de pasajero o tour_price, al guardar un
   producto por la via de WooCommerce, al activar el plugin, al actualizarlo
   y la primera vez que se ve una version nueva (por si los archivos se
   suben por FTP, donde no corre ningun hook de instalacion).

w) LA TARIFA DE ADULTO SE ENCUENTRA DE VERDAD. La funcion buscaba un tipo de
   pasajero llamado "Adulto" y en este sitio se llaman "Mayor de 13", "Entre
   5 y 12" y "Menor de 5": nunca coincidia y devolvia cero en los dieciocho
   tours, o sea que el respaldo no servia para nada. Ahora se toma la tarifa
   mas cara, que es siempre la de adulto (55.000 contra 27.500 del menor y
   5.000 del bebe) y que no se rompe si manana renombran las etiquetas.

x) EL TOUR 18 ENTRA EN LA TABLA. El Wine Tour con Picnic en Lujan de Cuyo
   existia como producto pero faltaba en la tabla de respaldo del
   JavaScript. Ahora estan los dieciocho.


NOVEDADES v1.4.0
----------------
Esta version junta lo que salio del QA previo a produccion, para no ir
instalando de a un arreglo por vez.

o) El carrito flotante ya no aparece en ninguna pantalla chica, no solo en
   la ficha del tour. En la portada quedaba justo encima del boton del
   hero: tapaba lo unico que esa pantalla quiere que el visitante toque.
   En escritorio sigue igual, ahi no molesta.

p) El campo de cantidad de personas de Bookings pasa de 26 a 44 px de alto
   y a 16 px de letra. Es el primer campo del formulario de reserva: abajo
   de 44 px el dedo falla seguido, y abajo de 16 px iOS agranda la pagina
   sola al enfocarlo.

q) El "Ver los N tours" ahora se corrige tambien en la ficha del tour. El
   numero sale de los tours publicados; antes solo se miraba el enlace del
   pie y en la ficha quedaba uno de mas.

r) El bloque de confianza del checkout decia "Reprogramacion y cancelacion
   sin cargo". La politica real es 100% de reintegro hasta 72 hs antes, asi
   que ahora dice "Cancelacion sin cargo hasta 72 hs antes". Prometer de
   mas ahi se paga despues por telefono.

s) Los precios del bloque de vistos recientemente salian a la inglesa
   ($53,000) mientras el resto del sitio usa punto ($53.000). Se unifican.
   Solo se toca la coma seguida de tres digitos: los centavos llevan dos y
   quedan intactos.

t) "Recently Viewed Tours" pasa a "Vistos recientemente". Estaba escrito a
   mano en el theme, por eso no lo alcanzaba la traduccion.

Los textos de r), s) y t) se repasan tambien cuando WooCommerce vuelve a
dibujar el carrito o el checkout por AJAX; si no, el texto viejo volvia
solo al primer cambio de cantidad.


NOVEDADES v1.3.4
----------------
m) La confirmacion de "lo agregamos a tu carrito" ahora tambien aparece
   cuando la pagina NO se recarga. Se probo en staging: el carrito lateral
   resuelve el alta por AJAX y el documento nunca se vuelve a cargar, asi
   que el aviso, que esperaba a la recarga, no salia nunca. Ahora, al
   enviar el formulario, se le pregunta al servidor hasta que el numero
   suba y el aviso sale en el acto. Si la pagina si se recarga, el aviso lo
   sigue mostrando la carga siguiente: nunca aparecen dos.

n) El globito del carrito de la cabecera se copia del carrito lateral cada
   vez que algo cambia, en lugar de leerse una sola vez al cargar. Ese
   globito no viaja en los fragmentos de WooCommerce, por eso quedaba en 0
   con el carrito ya cargado.

NOVEDADES v1.3.3
----------------
l) La version de cada asset ahora viaja en el NOMBRE del archivo
   (assets/ux.1757003872.js) y el .htaccess de assets/ le borra ese numero
   antes de servirlo. El "?ver=" de WordPress no servia: algo del lado del
   hosting se lo saca a todas las URLs de assets. Con esto, cada
   actualizacion del plugin se ve al instante, sin esperar a que caduque el
   CDN.

NOVEDADES v1.5.3
================

y) Se completo la traduccion despues de verla funcionando en el sitio.
   Faltaban: el boton "Book now" (el boton de reservar, nada menos),
   "Per Person" debajo del precio, "Share this tour", el aviso de
   JavaScript, las etiquetas Age / Availability / Departure Time /
   Return Time de la ficha, y el formulario de opiniones entero
   (Write A Review, Accomodation, Destination, Meals, Transport,
   Value For Money, Overall).

z) La pagina 404 quedaba a medias porque el theme no imprime la frase
   entera: la parte en el punto de exclamacion y saca cada mitad por
   su lado. Ahora cada mitad tiene su propia traduccion.

El mapa pasa de 22 a 39 textos.

NOVEDADES v1.5.3
================

aa) El recuadro de atributos de la ficha muestra la duracion real en
    horas en vez de "1 day". El theme solo guarda un contador de dias,
    asi que los 18 tours decian lo mismo aunque duren cinco horas o
    catorce. Ahora vempra_duracion_tour() la calcula con los horarios
    de salida y regreso que ya estaban cargados en cada ficha: el Tour
    Bodegas pasa a decir "5 hs".

    Cuando no hay una duracion unica que afirmar, queda el contador de
    dias traducido ("1 dia"). Pasa en dos casos reales: la Cabalgata al
    Atardecer, que sale 16:30 en verano y 14:30 en invierno, y el Wine
    Tour con Picnic, que todavia no tiene horarios cargados.

    La traduccion no se hace con un filtro global porque "day" aparece
    en medio panel; se toca solo ese recuadro, y solo cuando el texto
    es exactamente un numero seguido de day/days.

bb) "Tu carrito esta vacio" pasa a "Tu carrito esta vacio" con tildes.
    Ese texto vive en los ajustes del carrito lateral (Xoo Side Cart),
    no en un archivo de traduccion, asi que se corrige desde el mismo
    reemplazo de textos que ya usa el plugin.
