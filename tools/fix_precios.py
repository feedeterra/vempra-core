"""Iguala el precio de venta del producto al precio que la ficha ya publica.

Los 14 tours sin precio en WooCommerce se agregaban al carrito en $0 aunque la
pagina mostraba el valor. Se copia tour_price al producto. Las tres cabalgatas
(127, 128, 237) quedan afuera: estan atadas al dolar y las carga el dueno.
"""
import sys

sys.path.insert(0, 'tools')
import mcp

PRECIOS = {
    134: 247000, 126: 200000, 130: 170000, 131: 130000, 129: 130000,
    125: 126000, 369: 113000, 122: 109000, 371: 91000, 370: 91000, 123: 55000,
}

for pid, precio in PRECIOS.items():
    for clave in ('_regular_price', '_price'):
        r = mcp.call('wp_update_post_meta', {'post_id': pid, 'key': clave, 'value': str(precio)})
        if not (isinstance(r, dict) and r.get('result')):
            print('FALLO', pid, clave, r)
    print(pid, precio, 'ok')
