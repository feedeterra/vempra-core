"""Escribe en produccion los dos cambios de contenido que no dependen del plugin:

  1. Pagina 331 "Tours en Mendoza": reemplaza las tarjetas por las de
     /tmp/p331_nuevo.html (18 tarjetas, precios al dia, tarjeta de rafting de
     produccion intacta). Produccion tenia 10 precios viejos y le faltaba
     Wine Tour con Picnic.
  2. Fichas 523, 525 y 529: una sola linea, la de edades, queda igual en las
     tres segun el criterio "la edad es por experiencia".

NO toca la ficha 528: su contenido usa 37 clases vempra-* que hoy solo existen
en vempra-core 1.5.3, todavia sin instalar en produccion.

Guarda el estado previo en respaldo_contenido.json antes de escribir nada.
"""
import json
import sys
import time

sys.path.insert(0, 'tools')
import mcpprod as mcp

PAUSA = 2.5
PAGINA = 331
NUEVO = '/tmp/p331_nuevo.html'

EDADES_NUEVO = ('Todas las edades. Tarifa de niño de 3 a 12 años; '
                'menores de 3, tarifa reducida.')
EDADES = {
    523: 'Todas las edades. Niños menores de 6 años consultar (el viaje es largo).',
    525: 'Todas las edades. Niños desde 5 años, ideal para familias.',
    529: 'Todas las edades. Niños desde 5 años recomendado.',
}


def traer(post_id):
    r = mcp.call('wp_get_post', {'id': post_id})
    time.sleep(PAUSA)
    if not isinstance(r, dict):
        sys.exit('no pude leer el post %d: %s' % (post_id, r))
    return r.get('content') or ''


def escribir(post_id, contenido):
    r = mcp.call('wp_update_post', {'id': post_id, 'content': contenido})
    time.sleep(PAUSA)
    ok = isinstance(r, dict) and not str(r).startswith('Error')
    print('escrito' if ok else 'FALLO', post_id, '' if ok else r)
    return ok


nuevo_331 = open(NUEVO).read()
if len(nuevo_331) < 15000 or 'papayawhip' in nuevo_331:
    sys.exit('el html de la pagina 331 no pasa el control basico')

respaldo = {str(PAGINA): traer(PAGINA)}
for post_id in EDADES:
    respaldo[str(post_id)] = traer(post_id)

with open('respaldo_contenido.json', 'w') as f:
    json.dump(respaldo, f, ensure_ascii=False, indent=1)
print('respaldo guardado en respaldo_contenido.json (%d posts)' % len(respaldo))

escribir(PAGINA, nuevo_331)

for post_id, viejo in EDADES.items():
    texto = respaldo[str(post_id)]
    if viejo not in texto:
        print('SALTEADO', post_id, 'no encontre la linea de edades tal cual')
        continue
    escribir(post_id, texto.replace(viejo, EDADES_NUEVO))
