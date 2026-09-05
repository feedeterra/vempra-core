"""Sube Bodegas Mendoza (producto 124) de $53.000 a $55.000.

La planilla (columna VALOR OFICIAL) manda: decia 55.000 y el producto tenia
53.000. Era el unico desfasaje que quedaba entre planilla y WooCommerce.
"""
import sys

sys.path.insert(0, 'tools')
import mcp

PID = 124
PRECIO = 55000

for clave in ('_regular_price', '_price'):
    r = mcp.call('wp_update_post_meta', {'post_id': PID, 'key': clave, 'value': str(PRECIO)})
    if not (isinstance(r, dict) and r.get('result')):
        print('FALLO', PID, clave, r)
print(PID, PRECIO, 'ok')
