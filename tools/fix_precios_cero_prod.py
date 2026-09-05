"""Escribe el precio de WooCommerce en los 7 productos de produccion que lo
tenian vacio / en 0.

El precio que se escribe NO es una decision nueva: es exactamente el que la
ficha ya le muestra hoy al cliente (meta tour_price del tour vinculado). El
objetivo es solamente que el dato que sale hacia Tag Manager y GA4 deje de
viajar en 0; nadie paga distinto ni ve algo distinto por este cambio.

Antes de escribir guarda el valor previo en respaldo_precios_cero.json para
poder volver atras.
"""
import json
import sys
import time

sys.path.insert(0, 'tools')
import mcpprod as mcp

# producto -> (tour vinculado, precio que la ficha ya muestra)
PRECIOS = {
    128: (533, '165000'),   # Cabalgata Criolla - Medio Dia
    131: (538, '130000'),   # Rafting Medio Dia - 12 km
    134: (754, '247000'),   # Wine Tour con Picnic
    237: (534, '155000'),   # Cabalgata Al Atardecer
    369: (523, '113000'),   # Dia de Nieve en Las Lenas
    370: (524,  '91000'),   # Dia de Nieve en Los Puquios
    371: (525,  '91000'),   # Dia de Nieve en Penitentes
}

PAUSA = 2.5  # el endpoint devuelve 429 si se lo apura


def leer(post_id, clave):
    r = mcp.call('wp_get_post_meta', {'post_id': post_id, 'key': clave})
    time.sleep(PAUSA)
    if isinstance(r, dict):
        v = r.get(clave, r.get('value'))
        if isinstance(v, list):
            return v[0] if v else ''
        return v
    return r


respaldo = {}
for pid in PRECIOS:
    respaldo[pid] = {c: leer(pid, c) for c in ('_regular_price', '_price')}
    print('previo', pid, respaldo[pid])

with open('respaldo_precios_cero.json', 'w') as f:
    json.dump(respaldo, f, indent=2, ensure_ascii=False)
print('respaldo guardado')

for pid, (_tour, precio) in PRECIOS.items():
    for clave in ('_regular_price', '_price'):
        r = mcp.call('wp_update_post_meta',
                     {'post_id': pid, 'key': clave, 'value': precio})
        ok = isinstance(r, dict) and r.get('result')
        print('escrito' if ok else 'FALLO', pid, clave, precio, '' if ok else r)
        time.sleep(PAUSA)
