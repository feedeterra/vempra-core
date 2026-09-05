"""Fija las tres cabalgatas en $165.000.

Estaban en $0 en WooCommerce: la ficha publicaba el precio pero el carrito
cobraba nada. Federico decidio el 2026-09-04 fijarlas en pesos en vez de
dejarlas atadas al dolar.
"""
import sys

sys.path.insert(0, 'tools')
import mcp

PRECIO = 165000
CABALGATAS = (127, 128, 237)

for pid in CABALGATAS:
    for clave in ('_regular_price', '_price'):
        r = mcp.call('wp_update_post_meta', {'post_id': pid, 'key': clave, 'value': str(PRECIO)})
        if not (isinstance(r, dict) and r.get('result')):
            print('FALLO', pid, clave, r)
    print(pid, PRECIO, 'ok')
