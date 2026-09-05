"""Arma el contenido nuevo de la pagina "Tours en Mendoza" (post 331) y lo
valida contra los precios que produccion cobra hoy. No escribe nada.

Parte del texto de staging, que es el que tiene las 18 tarjetas y la grilla
al dia, y le corrige dos cosas que en staging estan mal para produccion:

  - La tarjeta de rafting. En staging el tour se llama "Rafting Dia Completo"
    y habla de Potrerillos; el tour que produccion vende bajo esa misma URL es
    "Rafting Medio Dia - 12 km". Se conserva la tarjeta de produccion tal cual.
  - Villavicencio 4x4. Staging dice 126.000; produccion cobra 127.000 desde
    que emparejamos los precios.

Deja el resultado en /tmp/p331_nuevo.html y avisa de cualquier tarjeta cuyo
precio no coincida con el que la tienda cobra.
"""
import json
import re
import sys

ORIGEN = '/tmp/contenido.json'
DESTINO = '/tmp/p331_nuevo.html'

RAFTING = '/tour/tour-rafting-medio-dia-12-km-en-el-rio-mendoza/'

# Lo que la tienda cobra hoy, verificado ficha por ficha en produccion.
PRECIOS = {
    'Tour Alta Montaña': 95000,
    'Cañón del Atuel': 109000,
    'Tour Villavicencio': 55000,
    'Tour Bodegas Mendoza': 55000,
    'Villavicencio 4x4': 127000,
    'Full Day Spa Cacheuta': 200000,
    'Cabalgata con Almuerzo': 165000,
    'Cabalgata Criolla': 165000,
    'Combo Termas + Rafting': 130000,
    'Full Day Aventura': 170000,
    'Rafting Medio Día': 130000,
    'Wine Tour Maipú': 185000,
    'Wine Tour Luján de Cuyo': 247000,
    'Wine Tour con Picnic': 247000,
    'Cabalgata al Atardecer': 165000,
    'Las Leñas': 113000,
    'Los Puquios': 91000,
    'Penitentes': 91000,
}


def tarjetas(texto):
    return {m.group(1): m.group(0)
            for m in re.finditer(r'<a href="(/tour/[^"]+)".*?</a>', texto, re.S)}


datos = json.load(open(ORIGEN))
prod = datos['331']['prod']['texto']
nuevo = datos['331']['stg']['texto']

# 1. la tarjeta de rafting vuelve a ser la de produccion
de_prod = tarjetas(prod)
de_stg = tarjetas(nuevo)
if RAFTING not in de_prod or RAFTING not in de_stg:
    sys.exit('no encontre la tarjeta de rafting en los dos textos')
nuevo = nuevo.replace(de_stg[RAFTING], de_prod[RAFTING])

# 2. Villavicencio 4x4 pasa a 127.000
antes = nuevo
nuevo = nuevo.replace('data-price="126000"', 'data-price="127000"')
nuevo = nuevo.replace('$126.000', '$127.000')
if nuevo == antes:
    print('aviso: no habia nada que corregir en Villavicencio 4x4')

# 3. validacion: cada tarjeta contra lo que la tienda cobra
print('%-26s %10s %12s' % ('tarjeta', 'data-price', 'texto visible'))
problemas = []
for href, bloque in sorted(tarjetas(nuevo).items()):
    nombre = re.search(r'data-name="([^"]*)"', bloque)
    precio = re.search(r'data-price="([^"]*)"', bloque)
    visible = re.search(r'vempra-shop-card-price-value">\$([\d.]+)<', bloque)
    nombre = nombre.group(1) if nombre else '?'
    precio = int(precio.group(1)) if precio else None
    vis = int(visible.group(1).replace('.', '')) if visible else None

    marca = ''
    real = PRECIOS.get(nombre)
    if real is None:
        marca = '  <<< nombre desconocido'
    elif precio != real or vis != real:
        marca = '  <<< deberia ser %d' % real
    if marca:
        problemas.append(nombre)
    print('%-26s %10s %12s%s' % (nombre[:26], precio, vis, marca))

print()
print('tarjetas:', len(tarjetas(nuevo)), '| largo:', len(nuevo))
print('experiencias que anuncia:',
      re.findall(r'vempra-shop-hero-eyebrow">([^<]*)<', nuevo))
print('URLs de staging que quedaron:',
      len(re.findall(r'papayawhip-hyena-382000', nuevo)))
print('problemas:', problemas if problemas else 'ninguno')

with open(DESTINO, 'w') as f:
    f.write(nuevo)
print('escrito para revisar:', DESTINO)
