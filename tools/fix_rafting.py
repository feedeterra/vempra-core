"""Empareja el Rafting Dia Completo en $130.000 y redondea el precio de nino
de la Cabalgata con Almuerzo Criollo.

El rafting mostraba $99.000 en la tarjeta y la ficha, pero el producto decia
$130.000. Federico decidio el 2026-09-04 quedarse con el mas caro. El tipo de
pasajero es lo que cobra, asi que sin este cambio el carrito seguia en 99.000.
"""
import sys

sys.path.insert(0, 'tools')
import mcp

CAMBIOS = {
    717: '130000',   # Rafting Dia Completo, Mayor de 13
    706: '115000',   # Cabalgata con Almuerzo, Entre 5 y 12 (venia 114999.97)
}

for pid, precio in CAMBIOS.items():
    r = mcp.call('wp_update_post_meta', {'post_id': pid, 'key': 'cost', 'value': precio})
    if not (isinstance(r, dict) and r.get('result')):
        print('FALLO', pid, r)
    print(pid, mcp.call('wp_get_post_meta', {'post_id': pid}).get('cost'))
