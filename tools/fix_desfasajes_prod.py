"""Empareja, en produccion, el precio que la ficha muestra con el que la
reserva realmente cobra, y redondea dos tarifas que tenian centavos.

Tres tours anunciaban menos de lo que cobraban al reservar. Se aplica el
criterio del duenio ("dejá el más caro y emparejá"): sube el precio mostrado
hasta el que ya se estaba cobrando. Nadie pasa a pagar mas de lo que pagaba;
lo que cambia es que el numero anunciado deja de ser mentira.

Guarda el estado previo en respaldo_desfasajes.json.
"""
import json
import sys
import time

sys.path.insert(0, 'tools')
import mcpprod as mcp

PAUSA = 2.5

# tour (meta tour_price)  -> valor nuevo
FICHAS = {
    530: '55000',    # Villavicencio Medio Dia: mostraba 50.000, cobraba 55.000
    531: '127000',   # Villavicencio 4x4:       mostraba 126.000, cobraba 127.000
    534: '165000',   # Cabalgata al Atardecer:  mostraba 155.000, cobraba 165.000
}

# producto (_price y _regular_price) -> valor nuevo
PRODUCTOS = {
    124: '55000',    # Bodegas: la planilla manda 55.000, el producto decia 53.000
    125: '127000',
    237: '165000',
}

# tipo de pasajero (meta cost) -> valor nuevo. Solo saca los centavos.
PASAJEROS = {
    698: '55000',    # Bodegas adulto,                 estaba en 54999.99
    706: '115000',   # Cabalgata con Almuerzo, nino,   estaba en 114999.97
}


def leer_meta(post_id, claves):
    r = mcp.call('wp_get_post_meta', {'post_id': post_id})
    time.sleep(PAUSA)
    if not isinstance(r, dict):
        return {'_error': str(r)[:120]}
    return {c: (r.get(c) or [None])[0] for c in claves}


def escribir(post_id, clave, valor):
    r = mcp.call('wp_update_post_meta',
                 {'post_id': post_id, 'key': clave, 'value': valor})
    time.sleep(PAUSA)
    ok = isinstance(r, dict) and r.get('result')
    print('escrito' if ok else 'FALLO', post_id, clave, valor, '' if ok else r)
    return bool(ok)


respaldo = {}
for t in FICHAS:
    respaldo['tour_%d' % t] = leer_meta(t, ['tour_price'])
for p in PRODUCTOS:
    respaldo['producto_%d' % p] = leer_meta(p, ['_price', '_regular_price'])
for p in PASAJEROS:
    respaldo['pasajero_%d' % p] = leer_meta(p, ['cost'])

with open('respaldo_desfasajes.json', 'w') as f:
    json.dump(respaldo, f, indent=2, ensure_ascii=False)
print('respaldo guardado:', json.dumps(respaldo, ensure_ascii=False))

for t, v in FICHAS.items():
    escribir(t, 'tour_price', v)
for p, v in PRODUCTOS.items():
    escribir(p, '_regular_price', v)
    escribir(p, '_price', v)
for p, v in PASAJEROS.items():
    escribir(p, 'cost', v)
